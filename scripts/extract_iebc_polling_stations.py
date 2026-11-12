#!/usr/bin/env python3

import csv
import re
from pathlib import Path

import pymupdf

SOURCE = Path(
    "data/iebc/raw/"
    "iebc_registered_voters_per_polling_station_2022.pdf"
)

OUTPUT = Path(
    "data/iebc/validated/"
    "iebc_2022_polling_stations_preview.csv"
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

def extract_row(words):
    result = {}

    for field, left, right in COLUMNS:
        selected = [
            (word[0], word[4])
            for word in words
            if left <= word[0] < right
        ]

        selected.sort(key=lambda item: item[0])

        result[field] = " ".join(
            value for _, value in selected
        ).strip()

    return result

def main():
    if not SOURCE.is_file():
        raise SystemExit("Source PDF not found.")

    if OUTPUT.exists():
        raise SystemExit(
            f"STOP: Output already exists: {OUTPUT}"
        )

    records = []
    rejected = []

    with pymupdf.open(SOURCE) as document:
        for page_index in range(min(3, len(document))):
            page = document[page_index]

            words = page.get_text("words", sort=True)

            rows = {}

            for word in words:
                x0, y0, x1, y1, text, *_ = word

                if y0 < 70 or y0 > 590:
                    continue

                row_key = round(y0 / 7.5)

                rows.setdefault(row_key, []).append(word)

            for row_key, row_words in sorted(rows.items()):
                record = extract_row(row_words)

                station_code = record["station_code"]
                voters = record["registered_voters"]

                if not re.fullmatch(
                    r"\d{15}", station_code
                ):
                    continue

                record["source_page"] = page_index + 1

                if not voters.isdigit():
                    rejected.append(record)
                    continue

                if (
                    station_code[:3]
                    != record["county_code"]
                    or station_code[3:6]
                    != record["constituency_code"]
                    or station_code[6:10]
                    != record["ward_code"]
                    or station_code[10:13]
                    != record["centre_code"]
                ):
                    rejected.append(record)
                    continue

                records.append(record)

    if rejected:
        print("WARNING: Rejected records:", len(rejected))
        for record in rejected[:10]:
            print(record)

        raise SystemExit(
            "STOP: Rejected records require review."
        )

    if not records:
        raise SystemExit(
            "STOP: No valid polling stations extracted."
        )

    OUTPUT.parent.mkdir(
        parents=True, exist_ok=True
    )

    with OUTPUT.open(
        "w",
        newline="",
        encoding="utf-8"
    ) as stream:
        writer = csv.DictWriter(
            stream,
            fieldnames=[
                name for name, _, _ in COLUMNS
            ] + ["source_page"]
        )

        writer.writeheader()
        writer.writerows(records)

    print("\n===== EXTRACTION PREVIEW =====")
    print("Pages processed: 3")
    print("Records extracted:", len(records))
    print("Rejected records:", len(rejected))
    print("Output:", OUTPUT)

    print("\nFIRST FIVE RECORDS:")

    for record in records[:5]:
        print(
            record["county_name"],
            "|",
            record["constituency_name"],
            "|",
            record["ward_name"],
            "|",
            record["station_code"],
            "| voters:",
            record["registered_voters"]
        )

    print("\nNo database records modified.")

if __name__ == "__main__":
    main()
