#!/usr/bin/env python3

from pathlib import Path
import json
import os
import sqlite3
import subprocess
import sys
import tempfile
import time
import xml.etree.ElementTree as ET

PROJECT = Path(__file__).resolve().parents[2]
HISTORICAL = (PROJECT / "database/database.sqlite").resolve()

SOURCE = Path(
    "/private/var/folders/vh/tvtndpdx34z9265m5175d4vr0000gn/"
    "T/kuraguard-identity-migration-x9wolrk0/identity-test.sqlite"
).resolve()

WORKER = PROJECT / (
    "tests/concurrency/aspirant_registration_worker.php"
)

if not SOURCE.is_file() or SOURCE == HISTORICAL:
    sys.exit("STOP: Safe migrated disposable database unavailable.")

with sqlite3.connect(SOURCE) as connection:
    if connection.execute("PRAGMA integrity_check").fetchone()[0] != "ok":
        sys.exit("STOP: Source integrity failure.")

    columns = {
        row[1]: row
        for row in connection.execute(
            "PRAGMA table_info(aspirant_contests)"
        )
    }

    identity = columns.get("contest_identity_key")

    if identity is None or identity[3] != 1:
        sys.exit("STOP: Identity constraint missing.")

    indexes = connection.execute(
        "PRAGMA index_list(aspirant_contests)"
    ).fetchall()

    if not any(
        row[1] == "aspirant_contest_identity_unique"
        and row[2] == 1
        for row in indexes
    ):
        sys.exit("STOP: Unique contest identity index missing.")

with tempfile.TemporaryDirectory(
    prefix="kuraguard-real-concurrency-"
) as folder:
    temporary = Path(folder)
    database = (temporary / "concurrency.sqlite").resolve()

    if database in (HISTORICAL, SOURCE):
        sys.exit("STOP: Unsafe destination.")

    with sqlite3.connect(SOURCE) as original:
        with sqlite3.connect(database) as destination:
            original.backup(destination)

    with sqlite3.connect(database) as connection:
        connection.execute("PRAGMA foreign_keys = ON")

        # Create an isolated user and dataset in the temporary DB.
        # The existing users schema is populated using a copied fixture.
        existing = connection.execute(
            """
            SELECT u.id
            FROM users u
            LEFT JOIN aspirants a ON a.user_id = u.id
            WHERE a.id IS NULL
            ORDER BY u.id
            LIMIT 1
            """
        ).fetchone()

        dataset = connection.execute(
            "SELECT id FROM electoral_datasets "
            "WHERE reference_year = 2022 "
            "AND status = 'historical' "
            "ORDER BY id DESC LIMIT 1"
        ).fetchone()

        if existing is None or dataset is None:
            sys.exit(
                "STOP: Disposable fixture lacks a user or historical dataset."
            )

        user_id = int(existing[0])
        dataset_id = int(dataset[0])

        prior = connection.execute(
            "SELECT COUNT(*) FROM aspirants WHERE user_id = ?",
            (user_id,)
        ).fetchone()[0]

        if prior:
            sys.exit("STOP: Fixture user already has an aspirant profile.")

    environment = os.environ.copy()
    environment.update({
        "APP_ENV": "testing",
        "DB_CONNECTION": "sqlite",
        "DB_DATABASE": str(database),
        "DB_URL": "",
    })

    start_at = time.time() + 1.0

    commands = [
        "php",
        str(WORKER),
        str(user_id),
        str(dataset_id),
        str(start_at),
    ]

    print("===== TWO-PROCESS REGISTRATION RACE =====", flush=True)
    print("Database:", database, flush=True)

    workers = [
        subprocess.Popen(
            commands,
            cwd=PROJECT,
            env=environment,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True,
        )
        for _ in range(2)
    ]

    outcomes = []

    for index, process in enumerate(workers, 1):
        try:
            stdout, stderr = process.communicate(timeout=30)
        except subprocess.TimeoutExpired:
            process.kill()
            stdout, stderr = process.communicate()
            outcomes.append({
                "worker": index,
                "status": "timeout",
                "stderr": stderr[-1000:],
            })
            continue

        outcomes.append({
            "worker": index,
            "exit_code": process.returncode,
            "stdout": stdout.strip()[-2000:],
            "stderr": stderr.strip()[-1000:],
        })

    print(json.dumps(outcomes, indent=2), flush=True)

    with sqlite3.connect(database) as connection:
        aspirants = connection.execute(
            "SELECT id FROM aspirants WHERE user_id = ?",
            (user_id,)
        ).fetchall()

        contests = connection.execute(
            """
            SELECT c.id
            FROM aspirant_contests c
            JOIN aspirants a ON a.id = c.aspirant_id
            WHERE a.user_id = ?
              AND c.office = 'president'
              AND c.election_year = 2027
            """,
            (user_id,)
        ).fetchall()

        integrity = connection.execute(
            "PRAGMA integrity_check"
        ).fetchone()[0]

    print("\n===== DATABASE RESULTS =====", flush=True)
    print("Aspirant profiles:", len(aspirants), flush=True)
    print("Matching contests:", len(contests), flush=True)
    print("SQLite integrity:", integrity, flush=True)

    successful_workers = 0

    for item in outcomes:
        if item.get("exit_code") != 0:
            continue

        try:
            response = json.loads(item.get("stdout", ""))
        except json.JSONDecodeError:
            continue

        if response.get("status") == "success":
            successful_workers += 1

    print("Successful workers:", successful_workers, flush=True)

    passed = (
        successful_workers == 1
        and len(outcomes) == 2
        and all("exit_code" in item for item in outcomes)
        and len(aspirants) == 1
        and len(contests) == 1
        and integrity == "ok"
    )

    print(
        "\nRESULT:",
        "PASS" if passed else "FAIL",
        flush=True
    )

    print("Temporary database automatically deleted.", flush=True)
    print("Historical IEBC database untouched.", flush=True)

    sys.exit(0 if passed else 1)
