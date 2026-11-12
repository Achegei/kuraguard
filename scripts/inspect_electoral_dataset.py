#!/usr/bin/env python3

import csv
import hashlib
import sys
from collections import Counter
from pathlib import Path

def inspect(path):
    source = Path(path)

    if not source.is_file():
        raise SystemExit(f"File not found: {source}")

    if source.suffix.lower() not in (".csv", ".tsv"):
        raise SystemExit("Only CSV and TSV are supported.")

    digest = hashlib.sha256()
    with source.open("rb") as stream:
        for block in iter(lambda: stream.read(1024 * 1024), b""):
            digest.update(block)

    delimiter = "\t" if source.suffix.lower() == ".tsv" else ","

    with source.open("r", encoding="utf-8-sig", newline="") as stream:
        reader = csv.DictReader(stream, delimiter=delimiter)
        columns = reader.fieldnames or []

        if not columns:
            raise SystemExit("No header row found.")

        print("\n===== ELECTORAL DATASET INSPECTION =====")
        print("File:", source.name)
        print("SHA256:", digest.hexdigest())
        print("\nCOLUMNS:")
        for column in columns:
            print(" -", column)

        row_count = 0
        empty = Counter()
        examples = {}
        duplicate_counts = Counter()

        code_columns = [
            col for col in columns
            if "code" in col.lower()
            or "station_id" in col.lower()
        ]

        for row in reader:
            row_count += 1

            for column in columns:
                value = (row.get(column) or "").strip()

                if not value:
                    empty[column] += 1
                elif column not in examples:
                    examples[column] = value

            for column in code_columns:
                value = (row.get(column) or "").strip()
                if value:
                    duplicate_counts[(column, value)] += 1

        print("\nTOTAL ROWS:", row_count)

        print("\nEXAMPLE VALUES:")
        for column, value in examples.items():
            print(f" {column}: {value[:100]}")

        print("\nMISSING VALUES:")
        for column, count in empty.items():
            print(f" {column}: {count}")

        print("\nREPEATED CODE VALUES:")
        for column in code_columns:
            duplicates = sum(
                1 for (col, _), count in duplicate_counts.items()
                if col == column and count > 1
            )
            print(f" {column}: {duplicates} repeated identifiers")

        print("\n===== INSPECTION COMPLETE =====")
        print("No database records modified.")

if __name__ == "__main__":
    if len(sys.argv) != 2:
        raise SystemExit(
            "Usage: python3 scripts/inspect_electoral_dataset.py FILE.csv"
        )
    inspect(sys.argv[1])
