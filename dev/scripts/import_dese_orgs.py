#!/usr/bin/env python3
"""
DESE Organization Import Script
Reads 17 HTML-table XLS files from data/dese-exports/ and imports into organizations.

Usage: python dev/scripts/import_dese_orgs.py [--dry-run]
"""

import os, sys, argparse
from pathlib import Path
import pandas as pd

FILE_TO_TYPE = {
    "public school districts": "Public School District",
    "public schools": "Public School",
    "charter public school": "Charter School",
    "collaborative programs": "Collaborative Program",
    "private schools": "Private School",
    "approved special education programs": "Approved SPED Program",
    "approved special education school": "Approved SPED School",
    "approved special education agency": "Approved SPED Agency",
    "unapproved special education school": "Unapproved SPED School",
    "title 1 status district": "Title 1 District",
    "title 1 status school": "Title 1 School",
    "chapter 74 career voc tech education": "Career/Voc Tech",
    "innovation schools and academies": "Innovation School",
    "alternative education school": "Alt Ed School",
    "alternative education programs": "Alt Ed Program",
    "tribal education agency": "Tribal",
    "educator preparation program provider": "EPPP",
}

COL_ALIASES = {
    "org code": "org_code",
    "organization code": "org_code",
    "org name": "org_name",
    "organization name": "org_name",
    "school name": "org_name",
    "district name": "org_name",
    "town": "town",
    "city": "town",
    "municipality": "town",
    "grade span": "grade_span",
    "grades": "grade_span",
    "grade": "grade_span",
    "title 1 status": "title_1_status",
    "title i status": "title_1_status",
    "function area": "function_area",
    "function": "function_area",
    "program area": "function_area",
    "district code": "parent_org_code",
}


def detect_org_type(filename):
    name = filename.lower().replace("dese organization search", "").replace("export.xls", "").replace("-", "").strip()
    for pattern, otype in FILE_TO_TYPE.items():
        if pattern in name:
            return otype
    return None


def normalize_columns(df):
    """Rename columns to canonical names."""
    mapping = {}
    for col in df.columns:
        key = str(col).strip().lower()
        if key in COL_ALIASES:
            mapping[col] = COL_ALIASES[key]
    return df.rename(columns=mapping)


def parse_xls(filepath, org_type):
    """Parse an HTML-table XLS file."""
    tables = pd.read_html(filepath)
    if not tables:
        return []

    df = tables[0]
    # First row might be the real header
    if str(df.iloc[0, 0]).strip().lower() in ["org name", "org code", "organization name"]:
        df.columns = df.iloc[0]
        df = df.iloc[1:]

    df = normalize_columns(df)

    if "org_code" not in df.columns or "org_name" not in df.columns:
        print(f"  WARNING: Cannot find org_code/org_name in {filepath}. Cols: {list(df.columns)[:8]}")
        return []

    # Clean and filter
    df = df.dropna(subset=["org_code", "org_name"])
    df["org_code"] = df["org_code"].astype(str).str.strip()
    df["org_name"] = df["org_name"].astype(str).str.strip()
    df = df[df["org_code"].str.len() > 0]
    df = df[df["org_code"] != "0"]
    df = df[df["org_code"] != "nan"]

    df["org_type"] = org_type
    for col in ["town", "grade_span", "title_1_status", "function_area"]:
        if col in df.columns:
            df[col] = df[col].astype(str).str.strip()
            df.loc[df[col].isin(["nan", ""]), col] = None

    return df.to_dict("records")


def resolve_parent(orgs):
    """Set parent_org_code for schools: first 4 digits + '0000' = district code."""
    codes = {o["org_code"] for o in orgs}
    for o in orgs:
        if o["org_type"] == "Public School" and len(o.get("org_code", "")) >= 8:
            district_code = o["org_code"][:4] + "0000"
            if district_code in codes:
                o["parent_org_code"] = district_code
    return orgs


def esc(val):
    if val is None or str(val).strip() == "":
        return "NULL"
    s = str(val).replace("\\", "\\\\").replace("'", "\\'")
    return "'" + s + "'"


def generate_sql(orgs):
    lines = [
        "-- Auto-generated DESE organization import",
        "-- Generated from 17 DESE HTML-table XLS files",
        "SET NAMES utf8mb4;", "",
        "-- Pass 1: Insert all organizations (parent_org_id = NULL)",
    ]
    for o in orgs:
        vals = [
            esc(o["org_code"]), esc(o["org_name"]), esc(o["org_type"]),
            esc(o.get("town")), esc(o.get("grade_span")),
            esc(o.get("title_1_status")), esc(o.get("function_area")),
        ]
        lines.append(
            "INSERT INTO organizations (org_code, org_name, org_type, town, grade_span, title_1_status, function_area) "
            "VALUES (" + ", ".join(vals) + ") "
            "ON DUPLICATE KEY UPDATE org_name=VALUES(org_name), town=VALUES(town), grade_span=VALUES(grade_span), updated_at=NOW();"
        )

    # Pass 2: Update parent_org_id for schools
    schools = [o for o in orgs if o.get("parent_org_code")]
    if schools:
        lines.append("")
        lines.append("-- Pass 2: Set parent_org_id for schools")
        for o in schools:
            lines.append(
                "UPDATE organizations SET parent_org_id = "
                "(SELECT id FROM (SELECT id, org_code FROM organizations) AS t WHERE org_code = " + esc(o["parent_org_code"]) + ") "
                "WHERE org_code = " + esc(o["org_code"]) + ";"
            )

    return "\n".join(lines)


def main():
    parser = argparse.ArgumentParser(description="Import DESE org XLS files")
    parser.add_argument("--dry-run", action="store_true")
    parser.add_argument("--output", default=None)
    args = parser.parse_args()

    exports_dir = Path(__file__).parent.parent.parent / "data" / "dese-exports"
    xls_files = sorted(exports_dir.glob("*.xls"))
    print(f"Found {len(xls_files)} XLS files")

    all_orgs = []
    for f in xls_files:
        otype = detect_org_type(f.name)
        orgs = parse_xls(str(f), otype)
        print(f"  {f.name[:60]:60s} -> {len(orgs):5d} orgs ({otype})")
        all_orgs.extend(orgs)

    # Deduplicate by org_code
    seen = {}
    for o in all_orgs:
        code = o.get("org_code")
        if code and code not in seen:
            seen[code] = o
    orgs = list(seen.values())

    orgs = resolve_parent(orgs)
    print(f"\nUnique orgs: {len(orgs)}")
    types = {}
    for o in orgs:
        t = o.get("org_type", "Unknown")
        types[t] = types.get(t, 0) + 1
    for t, c in sorted(types.items()):
        print(f"  {t}: {c}")

    sql = generate_sql(orgs)

    outpath = Path(args.output) if args.output else (
        Path(__file__).parent.parent / "backend" / "seed" / "seed_organizations.sql"
    )

    if args.dry_run:
        print(f"\nWould write {len(sql):,} bytes to {outpath}")
        print(sql[:500])
    else:
        outpath.write_text(sql, encoding="utf-8")
        print(f"\nWrote {len(sql):,} bytes to {outpath}")


if __name__ == "__main__":
    main()
