#!/usr/bin/env python3

import csv
import re
from collections import Counter
from pathlib import Path

import pymupdf

SOURCE = Path(
    "data/iebc/raw/"
    "iebc_registered_voters_per_polling_station_2022.pdf"
)

OUTPUT = Path(
    "data/iebc/validated/"
    "iebc_2022_polling_stations_preview_v2.csv"
)

COLUMNS = [
    ("county_code", 52, 77),
    ("county_name", 77, 144),
    ("constituency_code", 144, 170),
    ("constituency_name", 170, 252),
    ("ward_code", 252, 278),
    ("ward_name", 278, 370),
    ("centre_code", 370, 396),
    ("centre_name", 396, 517),
    ("station_code", 517, 577),
    ("station_name", 577, 686),
    ("registered_voters", 686, 750),
]

CODE_PATTERN = re.compile(r"^\d{15}$")


def extract_record(row_words):
    record = {}

    for field, left, right in COLUMNS:
        selected = sorted(
            (
                (word[0], word[4])
                for word in row_words
                if left <= word[0] < right
            ),
            key=lambda item: item[0],
        )

        record[field] = " ".join(
            value for _, value in selected
        ).strip()

    return record


def main():
    if not SOURCE.is_file():
        raise SystemExit("STOP: Source PDF missing.")

    if OUTPUT.exists():
        raise SystemExit(
            f"STOP: Output already exists: {OUTPUT}"
        )

    records = []
    errors = []
    source_codes = []

    with pymupdf.open(SOURCE) as document:
        for page_index in range(min(3, len(document))):
            page = document[page_index]

            words = page.get_text("words", sort=True)

            anchors = [
                word
                for word in words
                if CODE_PATTERN.fullmatch(word[4])
                and 517 <= word[0] < 577
            ]

            source_codes.extend(
                re.findall(
                    r"(?<!\d)\d{15}(?!\d)",
                    page.get_text("text"),
                )
            )

            print(
                f"Page {page_index + 1}: "
                f"{len(anchors)} station anchors"
            )

            for anchor in anchors:
                y = anchor[1]

                row_words = [
                    word
                    for word in words
                    if abs(word[1] - y) <= 1.5
                ]

                record = extract_record(row_words)
                record["source_page"] = page_index + 1

                code = record["station_code"]

                if not CODE_PATTERN.fullmatch(code):
                    errors.append(
                        (page_index + 1, code, "Invalid code")
                    )
                    continue

                if not record["registered_voters"].isdigit():
                    errors.append(
                        (page_index + 1, code, "Invalid voters")
                    )
                    continue

                expected = (
                    record["county_code"]
                    + record["constituency_code"]
                    + record["ward_code"]
                    + record["centre_code"]
                )

                if not code.startswith(expected):
                    errors.append(
                        (page_index + 1, code, "Hierarchy mismatch")
                    )
                    continue

                if any(
                    not value
                    for value in record.values()
                ):
                    errors.append(
                        (page_index + 1, code, "Missing field")
                    )
                    continue

                records.append(record)

    csv_codes = [
        record["station_code"]
        for record in records
    ]

    source_counter = Counter(source_codes)
    extracted_counter = Counter(csv_codes)

    missing = list(
        (source_counter - extracted_counter).elements()
    )

    unexpected = list(
        (extracted_counter - source_counter).elements()
    )

    duplicates = [
        code
        for code, count in extracted_counter.items()
        if count > 1
    ]

    print("\n===== RECONCILIATION =====")
    print("Source station codes:", len(source_codes))
    print("Extracted records:", len(records))
    print("Missing codes:", len(missing))
    print("Unexpected codes:", len(unexpected))
    print("Duplicate codes:", len(duplicates))
    print("Validation errors:", len(errors))

    if missing:
        print("Missing examples:", missing[:10])

    if unexpected:
        print("Unexpected examples:", unexpected[:10])

    if duplicates:
        print("Duplicate examples:", duplicates[:10])

    if errors:
        print("Error examples:", errors[:10])

    if (
        errors
        or missing
        or unexpected
        or duplicates
        or not records
    ):
        raise SystemExit(
            "STOP: Reconciliation failed. "
            "No CSV written."
        )

    OUTPUT.parent.mkdir(
        parents=True,
        exist_ok=True,
    )

    with OUTPUT.open(
        "w",
        newline="",
        encoding="utf-8",
    ) as handle:
        writer = csv.DictWriter(
            handle,
            fieldnames=[
                name for name, _, _ in COLUMNS
            ] + ["source_page"],
        )

        writer.writeheader()
        writer.writerows(records)

    print("\n===== SUCCESS =====")
    print("CSV:", OUTPUT)
    print("Records:", len(records))
    print("No database changes.")


if __name__ == "__main__":
    main()
