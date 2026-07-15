<?php declare(strict_types=1);
/**
 * Counties migration — create table, seed 14 MA counties,
 * add county_id to organizations, map towns to counties.
 *
 * Usage:  php dev/backend/migrate_counties.php
 */

require __DIR__ . '/../../dev/app/bootstrap.php';

use App\Core\Database;

echo "=== Counties Migration ===\n";

// Step 1: Create counties table
echo "Creating counties table... ";
Database::execute("CREATE TABLE IF NOT EXISTS counties (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    county_name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "OK\n";

// Step 2: Seed 14 counties
echo "Seeding counties... ";
$counties = [
    ['Barnstable','barnstable'],['Berkshire','berkshire'],['Bristol','bristol'],
    ['Dukes','dukes'],['Essex','essex'],['Franklin','franklin'],['Hampden','hampden'],
    ['Hampshire','hampshire'],['Middlesex','middlesex'],['Nantucket','nantucket'],
    ['Norfolk','norfolk'],['Plymouth','plymouth'],['Suffolk','suffolk'],['Worcester','worcester'],
];
foreach ($counties as [$name, $slug]) {
    Database::execute(
        "INSERT IGNORE INTO counties (county_name, slug) VALUES (?, ?)",
        [$name, $slug]
    );
}
$rowCount = Database::fetchColumn("SELECT COUNT(*) FROM counties");
echo "OK ({$rowCount} rows)\n";

// Step 3: Add county_id to organizations
echo "Adding county_id column to organizations... ";
try {
    Database::execute("ALTER TABLE organizations ADD COLUMN county_id INT UNSIGNED NULL AFTER town");
    Database::execute("ALTER TABLE organizations ADD INDEX idx_county_id (county_id)");
    echo "OK\n";
} catch (\Exception $e) {
    // Column may already exist
    echo "SKIPPED (column may exist): " . $e->getMessage() . "\n";
}

// Step 4: Map towns to counties
echo "Mapping towns to counties... ";
$townCounty = [
    'Abington' => 'plymouth','Acton' => 'middlesex','Acushnet' => 'bristol',
    'Adams' => 'berkshire','Agawam' => 'hampden','Alford' => 'berkshire',
    'Amesbury' => 'essex','Amherst' => 'hampshire','Andover' => 'essex',
    'Aquinnah' => 'dukes','Arlington' => 'middlesex','Ashburnham' => 'worcester',
    'Ashby' => 'middlesex','Ashfield' => 'franklin','Ashland' => 'middlesex',
    'Athol' => 'worcester','Attleboro' => 'bristol','Auburn' => 'worcester',
    'Avon' => 'norfolk','Ayer' => 'middlesex','Barnstable' => 'barnstable',
    'Barre' => 'worcester','Becket' => 'berkshire','Bedford' => 'middlesex',
    'Belchertown' => 'hampshire','Bellingham' => 'norfolk','Belmont' => 'middlesex',
    'Berkley' => 'bristol','Berlin' => 'worcester','Bernardston' => 'franklin',
    'Beverly' => 'essex','Billerica' => 'middlesex','Blackstone' => 'worcester',
    'Blandford' => 'hampden','Bolton' => 'worcester','Boston' => 'suffolk',
    'Bourne' => 'barnstable','Boxborough' => 'middlesex','Boxford' => 'essex',
    'Boylston' => 'worcester','Braintree' => 'norfolk','Brewster' => 'barnstable',
    'Bridgewater' => 'plymouth','Brimfield' => 'hampden','Brockton' => 'plymouth',
    'Brookfield' => 'worcester','Brookline' => 'norfolk','Buckland' => 'franklin',
    'Burlington' => 'middlesex','Cambridge' => 'middlesex','Canton' => 'norfolk',
    'Carlisle' => 'middlesex','Carver' => 'plymouth','Charlemont' => 'franklin',
    'Charlton' => 'worcester','Chatham' => 'barnstable','Chelmsford' => 'middlesex',
    'Chelsea' => 'suffolk','Cheshire' => 'berkshire','Chester' => 'hampden',
    'Chesterfield' => 'hampshire','Chicopee' => 'hampden','Chilmark' => 'dukes',
    'Clarksburg' => 'berkshire','Clinton' => 'worcester','Cohasset' => 'norfolk',
    'Colrain' => 'franklin','Concord' => 'middlesex','Conway' => 'franklin',
    'Cummington' => 'hampshire','Dalton' => 'berkshire','Danvers' => 'essex',
    'Dartmouth' => 'bristol','Dedham' => 'norfolk','Deerfield' => 'franklin',
    'Dennis' => 'barnstable','Dighton' => 'bristol','Douglas' => 'worcester',
    'Dover' => 'norfolk','Dracut' => 'middlesex','Dudley' => 'worcester',
    'Dunstable' => 'middlesex','Duxbury' => 'plymouth','East Bridgewater' => 'plymouth',
    'East Brookfield' => 'worcester','East Longmeadow' => 'hampden',
    'Eastham' => 'barnstable','Easthampton' => 'hampshire','Easton' => 'bristol',
    'Edgartown' => 'dukes','Egremont' => 'berkshire','Erving' => 'franklin',
    'Essex' => 'essex','Everett' => 'middlesex','Fairhaven' => 'bristol',
    'Fall River' => 'bristol','Falmouth' => 'barnstable','Fitchburg' => 'worcester',
    'Florida' => 'berkshire','Foxborough' => 'norfolk','Framingham' => 'middlesex',
    'Franklin' => 'norfolk','Freetown' => 'bristol','Gardner' => 'worcester',
    'Georgetown' => 'essex','Gill' => 'franklin','Gloucester' => 'essex',
    'Goshen' => 'hampshire','Gosnold' => 'dukes','Grafton' => 'worcester',
    'Granby' => 'hampshire','Granville' => 'hampden','Great Barrington' => 'berkshire',
    'Greenfield' => 'franklin','Groton' => 'middlesex','Groveland' => 'essex',
    'Hadley' => 'hampshire','Halifax' => 'plymouth','Hamilton' => 'essex',
    'Hampden' => 'hampden','Hancock' => 'berkshire','Hanover' => 'plymouth',
    'Hanson' => 'plymouth','Hardwick' => 'worcester','Harvard' => 'worcester',
    'Harwich' => 'barnstable','Hatfield' => 'hampshire','Haverhill' => 'essex',
    'Hawley' => 'franklin','Heath' => 'franklin','Hingham' => 'plymouth',
    'Hinsdale' => 'berkshire','Holbrook' => 'norfolk','Holden' => 'worcester',
    'Holland' => 'hampden','Holliston' => 'middlesex','Holyoke' => 'hampden',
    'Hopedale' => 'worcester','Hopkinton' => 'middlesex','Hubbardston' => 'worcester',
    'Hudson' => 'middlesex','Hull' => 'plymouth','Huntington' => 'hampshire',
    'Ipswich' => 'essex','Kingston' => 'plymouth','Lakeville' => 'plymouth',
    'Lancaster' => 'worcester','Lanesborough' => 'berkshire','Lawrence' => 'essex',
    'Lee' => 'berkshire','Leicester' => 'worcester','Lenox' => 'berkshire',
    'Leominster' => 'worcester','Leverett' => 'franklin','Lexington' => 'middlesex',
    'Leyden' => 'franklin','Lincoln' => 'middlesex','Littleton' => 'middlesex',
    'Longmeadow' => 'hampden','Lowell' => 'middlesex','Ludlow' => 'hampden',
    'Lunenburg' => 'worcester','Lynn' => 'essex','Lynnfield' => 'essex',
    'Malden' => 'middlesex','Manchester-by-the-Sea' => 'essex','Mansfield' => 'bristol',
    'Marblehead' => 'essex','Marion' => 'plymouth','Marlborough' => 'middlesex',
    'Marshfield' => 'plymouth','Mashpee' => 'barnstable','Mattapoisett' => 'plymouth',
    'Maynard' => 'middlesex','Medfield' => 'norfolk','Medford' => 'middlesex',
    'Medway' => 'norfolk','Melrose' => 'middlesex','Mendon' => 'worcester',
    'Merrimac' => 'essex','Methuen' => 'essex','Middleborough' => 'plymouth',
    'Middlefield' => 'hampshire','Middleton' => 'essex','Milford' => 'worcester',
    'Millbury' => 'worcester','Millis' => 'norfolk','Millville' => 'worcester',
    'Milton' => 'norfolk','Monroe' => 'franklin','Monson' => 'hampden',
    'Montague' => 'franklin','Monterey' => 'berkshire','Montgomery' => 'hampden',
    'Mount Washington' => 'berkshire','Nahant' => 'essex','Nantucket' => 'nantucket',
    'Natick' => 'middlesex','Needham' => 'norfolk','New Ashford' => 'berkshire',
    'New Bedford' => 'bristol','New Braintree' => 'worcester',
    'New Marlborough' => 'berkshire','New Salem' => 'franklin',
    'Newbury' => 'essex','Newburyport' => 'essex','Newton' => 'middlesex',
    'Norfolk' => 'norfolk','North Adams' => 'berkshire','North Andover' => 'essex',
    'North Attleborough' => 'bristol','North Brookfield' => 'worcester',
    'North Reading' => 'middlesex','Northampton' => 'hampshire',
    'Northborough' => 'worcester','Northbridge' => 'worcester','Northfield' => 'franklin',
    'Norton' => 'bristol','Norwell' => 'plymouth','Norwood' => 'norfolk',
    'Oak Bluffs' => 'dukes','Oakham' => 'worcester','Orange' => 'franklin',
    'Orleans' => 'barnstable','Otis' => 'berkshire','Oxford' => 'worcester',
    'Palmer' => 'hampden','Paxton' => 'worcester','Peabody' => 'essex',
    'Pelham' => 'hampshire','Pembroke' => 'plymouth','Pepperell' => 'middlesex',
    'Peru' => 'berkshire','Petersham' => 'worcester','Phillipston' => 'worcester',
    'Pittsfield' => 'berkshire','Plainfield' => 'hampshire','Plainville' => 'norfolk',
    'Plymouth' => 'plymouth','Plympton' => 'plymouth','Princeton' => 'worcester',
    'Provincetown' => 'barnstable','Quincy' => 'norfolk','Randolph' => 'norfolk',
    'Raynham' => 'bristol','Reading' => 'middlesex','Rehoboth' => 'bristol',
    'Revere' => 'suffolk','Richmond' => 'berkshire','Rochester' => 'plymouth',
    'Rockland' => 'plymouth','Rockport' => 'essex','Rowe' => 'franklin',
    'Rowley' => 'essex','Royalston' => 'worcester','Russell' => 'hampden',
    'Rutland' => 'worcester','Salem' => 'essex','Salisbury' => 'essex',
    'Sandisfield' => 'berkshire','Sandwich' => 'barnstable','Saugus' => 'essex',
    'Savoy' => 'berkshire','Scituate' => 'plymouth','Seekonk' => 'bristol',
    'Sharon' => 'norfolk','Sheffield' => 'berkshire','Shelburne' => 'franklin',
    'Sherborn' => 'middlesex','Shirley' => 'middlesex','Shrewsbury' => 'worcester',
    'Shutesbury' => 'franklin','Somerset' => 'bristol','Somerville' => 'middlesex',
    'South Hadley' => 'hampshire','Southampton' => 'hampshire',
    'Southborough' => 'worcester','Southbridge' => 'worcester','Southwick' => 'hampden',
    'Spencer' => 'worcester','Springfield' => 'hampden','Sterling' => 'worcester',
    'Stockbridge' => 'berkshire','Stoneham' => 'middlesex','Stoughton' => 'norfolk',
    'Stow' => 'middlesex','Sturbridge' => 'worcester','Sudbury' => 'middlesex',
    'Sunderland' => 'franklin','Sutton' => 'worcester','Swampscott' => 'essex',
    'Swansea' => 'bristol','Taunton' => 'bristol','Templeton' => 'worcester',
    'Tewksbury' => 'middlesex','Tisbury' => 'dukes','Tolland' => 'hampden',
    'Topsfield' => 'essex','Townsend' => 'middlesex','Truro' => 'barnstable',
    'Tyngsborough' => 'middlesex','Tyringham' => 'berkshire','Upton' => 'worcester',
    'Uxbridge' => 'worcester','Wakefield' => 'middlesex','Wales' => 'hampden',
    'Walpole' => 'norfolk','Waltham' => 'middlesex','Ware' => 'hampshire',
    'Wareham' => 'plymouth','Warren' => 'worcester','Warwick' => 'franklin',
    'Washington' => 'berkshire','Watertown' => 'middlesex','Wayland' => 'middlesex',
    'Webster' => 'worcester','Wellesley' => 'norfolk','Wellfleet' => 'barnstable',
    'Wendell' => 'franklin','Wenham' => 'essex','West Boylston' => 'worcester',
    'West Bridgewater' => 'plymouth','West Brookfield' => 'worcester',
    'West Newbury' => 'essex','West Springfield' => 'hampden',
    'West Stockbridge' => 'berkshire','West Tisbury' => 'dukes',
    'Westborough' => 'worcester','Westfield' => 'hampden','Westford' => 'middlesex',
    'Westhampton' => 'hampshire','Westminster' => 'worcester','Weston' => 'middlesex',
    'Westport' => 'bristol','Westwood' => 'norfolk','Weymouth' => 'norfolk',
    'Whately' => 'franklin','Whitman' => 'plymouth','Wilbraham' => 'hampden',
    'Williamsburg' => 'hampshire','Williamstown' => 'berkshire','Wilmington' => 'middlesex',
    'Winchendon' => 'worcester','Winchester' => 'middlesex','Windsor' => 'berkshire',
    'Winthrop' => 'suffolk','Woburn' => 'middlesex','Worcester' => 'worcester',
    'Worthington' => 'hampshire','Wrentham' => 'norfolk','Yarmouth' => 'barnstable',
];

$updated = 0;
foreach ($townCounty as $town => $slug) {
    $affected = Database::execute(
        "UPDATE organizations SET county_id = (SELECT id FROM counties WHERE slug = ?) WHERE LOWER(town) = ?",
        [$slug, strtolower($town)]
    );
    $updated += $affected;
}
echo "OK ({$updated} rows updated)\n";

// Verify
$mappedCount = Database::fetchColumn("SELECT COUNT(*) FROM organizations WHERE county_id IS NOT NULL");
$orgCount = Database::fetchColumn("SELECT COUNT(*) FROM organizations");
echo "=== Done: {$mappedCount}/{$orgCount} organizations mapped to counties ===\n";
