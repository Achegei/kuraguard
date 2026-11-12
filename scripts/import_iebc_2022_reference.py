#!/usr/bin/env python3

"""Import verified IEBC 2022 historical reference data into SQLite."""

import argparse
import hashlib
import importlib.util
import re
import sqlite3
from collections import Counter
from datetime import datetime, timezone
from pathlib import Path

import pymupdf


ROOT = Path(__file__).resolve().parents[1]
EXTRACTOR = ROOT / "scripts/extract_iebc_polling_stations_v2.py"

EXPECTED_HASH = (
    "7d9144ddba1e866fc6a0ba62350c364c01613dd073c19e014451033f99fc3675"
)

DATASET_VERSION = "iebc-2022-registered-voters-per-polling-station"
EXPECTED_STATIONS = 46229
EXPECTED_VOTERS = 22120458

TABLES = [
    "electoral_datasets",
    "electoral_counties",
    "electoral_special_categories",
    "electoral_constituencies",
    "electoral_wards",
    "electoral_polling_centres",
    "electoral_polling_stations",
    "electoral_register_snapshots",
]


def load_extractor():
    spec = importlib.util.spec_from_file_location(
        "iebc_extractor_v2", EXTRACTOR
    )
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


def sha256_file(path):
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def extract_and_validate():
    module = load_extractor()
    source = ROOT / module.SOURCE

    if not source.is_file():
        raise RuntimeError("Original IEBC PDF missing.")

    if sha256_file(source) != EXPECTED_HASH:
        raise RuntimeError("IEBC PDF checksum mismatch.")

    records = []
    source_codes = Counter()
    extracted_codes = Counter()

    with pymupdf.open(source) as document:
        if len(document) != 712:
            raise RuntimeError("Unexpected PDF page count.")

        for page_index, page in enumerate(document):
            words = page.get_text("words", sort=True)

            source_codes.update(
                re.findall(
                    r"(?<!\d)\d{15}(?!\d)",
                    page.get_text("text"),
                )
            )

            anchors = [
                word for word in words
                if module.CODE_PATTERN.fullmatch(word[4])
                and 517 <= word[0] < 577
            ]

            for anchor in anchors:
                row_words = [
                    word for word in words
                    if abs(word[1] - anchor[1]) <= 1.5
                ]

                record = module.extract_record(row_words)
                record["source_page"] = page_index + 1

                code = record["station_code"]
                prefix = (
                    record["county_code"]
                    + record["constituency_code"]
                    + record["ward_code"]
                    + record["centre_code"]
                )

                if (
                    not module.CODE_PATTERN.fullmatch(code)
                    or not code.startswith(prefix)
                    or len(prefix) != 13
                    or not record["registered_voters"].isdigit()
                    or any(not value for value in record.values())
                ):
                    raise RuntimeError(
                        f"Invalid source record on page {page_index + 1}: {code}"
                    )

                records.append(record)
                extracted_codes[code] += 1

            if (page_index + 1) % 100 == 0:
                print(f"Extracted {page_index + 1}/712 pages", flush=True)

    voters = sum(int(r["registered_voters"]) for r in records)

    if (
        len(records) != EXPECTED_STATIONS
        or len(extracted_codes) != EXPECTED_STATIONS
        or source_codes != extracted_codes
        or voters != EXPECTED_VOTERS
    ):
        raise RuntimeError("National reconciliation failed.")

    return records


def insert_row(db, table, values):
    columns = list(values)
    placeholders = ", ".join("?" for _ in columns)
    column_sql = ", ".join(columns)

    cursor = db.execute(
        f"INSERT INTO {table} ({column_sql}) VALUES ({placeholders})",
        tuple(values.values()),
    )
    return cursor.lastrowid


def import_records(db, records):
    now = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M:%S")

    for table in TABLES:
        count = db.execute(f"SELECT COUNT(*) FROM {table}").fetchone()[0]
        if count:
            raise RuntimeError(
                f"Refusing import: {table} already contains {count} rows."
            )

    dataset_id = insert_row(db, "electoral_datasets", {
        "name": "IEBC 2022 Registered Voters Per Polling Station",
        "version": DATASET_VERSION,
        "source_organization": "Independent Electoral and Boundaries Commission",
        "source_url": "https://www.iebc.or.ke/docs/rov_per_polling_station.pdf",
        "source_hash": EXPECTED_HASH,
        "reference_year": 2022,
        "status": "historical",
        "notes": (
            "Historical 2022 source; not an authoritative 2027 register. "
            "Extracted totals require independent published-total verification."
        ),
        "created_at": now,
        "updated_at": now,
    })

    counties = {}
    categories = {}
    constituencies = {}
    wards = {}
    centres = {}

    for record in records:
        county_code = record["county_code"]
        constituency_code = record["constituency_code"]
        ward_code = record["ward_code"]
        centre_code = record["centre_code"]
        station_code = record["station_code"]

        is_special = county_code in ("048", "049")

        if is_special:
            if county_code not in categories:
                categories[county_code] = insert_row(
                    db, "electoral_special_categories", {
                        "code": county_code,
                        "name": record["county_name"],
                        "created_at": now,
                        "updated_at": now,
                    }
                )
        elif county_code not in counties:
            counties[county_code] = insert_row(
                db, "electoral_counties", {
                    "code": county_code,
                    "name": record["county_name"],
                    "created_at": now,
                    "updated_at": now,
                }
            )

        if constituency_code not in constituencies:
            constituencies[constituency_code] = insert_row(
                db, "electoral_constituencies", {
                    "county_id": (
                        None if is_special else counties[county_code]
                    ),
                    "special_category_id": (
                        categories[county_code] if is_special else None
                    ),
                    "code": constituency_code,
                    "name": record["constituency_name"],
                    "created_at": now,
                    "updated_at": now,
                }
            )

        if ward_code not in wards:
            wards[ward_code] = insert_row(
                db, "electoral_wards", {
                    "constituency_id": constituencies[constituency_code],
                    "code": ward_code,
                    "name": record["ward_name"],
                    "created_at": now,
                    "updated_at": now,
                }
            )

        centre_key = (ward_code, centre_code)

        if centre_key not in centres:
            centres[centre_key] = insert_row(
                db, "electoral_polling_centres", {
                    "ward_id": wards[ward_code],
                    "code": centre_code,
                    "name": record["centre_name"],
                    "created_at": now,
                    "updated_at": now,
                }
            )

        station_id = insert_row(
            db, "electoral_polling_stations", {
                "polling_centre_id": centres[centre_key],
                "code": station_code,
                "name": record["station_name"],
                "stream_number": station_code[-2:],
                "is_active": 1,
                "created_at": now,
                "updated_at": now,
            }
        )

        insert_row(db, "electoral_register_snapshots", {
            "dataset_id": dataset_id,
            "polling_station_id": station_id,
            "registered_voters": int(record["registered_voters"]),
            "created_at": now,
            "updated_at": now,
        })

    print("Dataset ID:", dataset_id)
    print("Counties:", len(counties))
    print("Special categories:", len(categories))
    print("Constituencies:", len(constituencies))
    print("Wards:", len(wards))
    print("Polling centres:", len(centres))
    print("Polling stations:", len(records))


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--database", required=True)
    parser.add_argument(
        "--execute",
        action="store_true",
        help="Perform import into the explicitly supplied SQLite database.",
    )
    args = parser.parse_args()

    database = Path(args.database).resolve()
    working_database = (ROOT / "database/database.sqlite").resolve()

    if database == working_database:
        raise SystemExit(
            "STOP: Direct import into working database is prohibited."
        )

    if not database.is_file():
        raise SystemExit("STOP: Target SQLite database does not exist.")

    print("Target database:", database)
    records = extract_and_validate()
    print("Validated records:", len(records))

    if not args.execute:
        print("DRY RUN: No database writes performed.")
        return

    db = sqlite3.connect(database)
    db.execute("PRAGMA foreign_keys = ON")
    db.execute("PRAGMA busy_timeout = 5000")

    try:
        db.execute("BEGIN IMMEDIATE")
        import_records(db, records)

        violations = db.execute(
            "PRAGMA foreign_key_check"
        ).fetchall()

        if violations:
            raise RuntimeError(
                f"Foreign-key violations: {violations[:5]}"
            )

        db.commit()
        print("PASS: Import transaction committed.")

    except Exception:
        db.rollback()
        raise
    finally:
        db.close()


if __name__ == "__main__":
    main()
