-- ============================================================================
-- Migration 003: Counties table + county_id FK on organizations
-- ============================================================================

CREATE TABLE IF NOT EXISTS counties (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    county_name     VARCHAR(100)    NOT NULL,
    slug            VARCHAR(100)    NOT NULL UNIQUE,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed 14 MA counties
INSERT IGNORE INTO counties (county_name, slug) VALUES
('Barnstable', 'barnstable'),
('Berkshire', 'berkshire'),
('Bristol', 'bristol'),
('Dukes', 'dukes'),
('Essex', 'essex'),
('Franklin', 'franklin'),
('Hampden', 'hampden'),
('Hampshire', 'hampshire'),
('Middlesex', 'middlesex'),
('Nantucket', 'nantucket'),
('Norfolk', 'norfolk'),
('Plymouth', 'plymouth'),
('Suffolk', 'suffolk'),
('Worcester', 'worcester');

-- Add county_id to organizations
ALTER TABLE organizations
  ADD COLUMN county_id INT UNSIGNED NULL AFTER town,
  ADD INDEX idx_county_id (county_id),
  ADD CONSTRAINT fk_org_county
    FOREIGN KEY (county_id) REFERENCES counties(id)
    ON DELETE SET NULL;

-- Town → county mapping for MA school districts
-- This updates all orgs whose town matches one of these mappings
UPDATE organizations SET county_id = (SELECT id FROM counties WHERE slug = 'barnstable')  WHERE town IN ('Barnstable', 'Bourne', 'Brewster', 'Chatham', 'Dennis', 'Eastham', 'Falmouth', 'Harwich', 'Mashpee', 'Orleans', 'Provincetown', 'Sandwich', 'Truro', 'Wellfleet', 'Yarmouth');
UPDATE organizations SET county_id = (SELECT id FROM counties WHERE slug = 'berkshire')   WHERE town IN ('Adams', 'Alford', 'Becket', 'Cheshire', 'Clarksburg', 'Dalton', 'Egremont', 'Florida', 'Great Barrington', 'Hancock', 'Hinsdale', 'Lanesborough', 'Lee', 'Lenox', 'Monterey', 'Mount Washington', 'New Ashford', 'New Marlborough', 'North Adams', 'Otis', 'Peru', 'Pittsfield', 'Richmond', 'Sandisfield', 'Savoy', 'Sheffield', 'Stockbridge', 'Tyringham', 'Washington', 'West Stockbridge', 'Williamstown', 'Windsor');
UPDATE organizations SET county_id = (SELECT id FROM counties WHERE slug = 'bristol')      WHERE town IN ('Acushnet', 'Attleboro', 'Berkley', 'Dartmouth', 'Dighton', 'Easton', 'Fairhaven', 'Fall River', 'Freetown', 'Mansfield', 'New Bedford', 'North Attleborough', 'Norton', 'Raynham', 'Rehoboth', 'Seekonk', 'Somerset', 'Swansea', 'Taunton', 'Westport');
UPDATE organizations SET county_id = (SELECT id FROM counties WHERE slug = 'dukes')        WHERE town IN ('Aquinnah', 'Chilmark', 'Edgartown', 'Gosnold', 'Oak Bluffs', 'Tisbury', 'West Tisbury');
UPDATE organizations SET county_id = (SELECT id FROM counties WHERE slug = 'essex')        WHERE town IN ('Amesbury', 'Andover', 'Beverly', 'Boxford', 'Danvers', 'Essex', 'Georgetown', 'Gloucester', 'Groveland', 'Hamilton', 'Haverhill', 'Ipswich', 'Lawrence', 'Lynn', 'Lynnfield', 'Manchester-by-the-Sea', 'Marblehead', 'Merrimac', 'Methuen', 'Middleton', 'Nahant', 'Newbury', 'Newburyport', 'North Andover', 'Peabody', 'Rockport', 'Rowley', 'Salem', 'Salisbury', 'Saugus', 'Swampscott', 'Topsfield', 'Wenham', 'West Newbury');
UPDATE organizations SET county_id = (SELECT id FROM counties WHERE slug = 'franklin')     WHERE town IN ('Ashfield', 'Bernardston', 'Buckland', 'Charlemont', 'Colrain', 'Conway', 'Deerfield', 'Erving', 'Gill', 'Greenfield', 'Hawley', 'Heath', 'Leverett', 'Leyden', 'Monroe', 'Montague', 'New Salem', 'Northfield', 'Orange', 'Rowe', 'Shelburne', 'Shutesbury', 'Sunderland', 'Warwick', 'Wendell', 'Whately');
UPDATE organizations SET county_id = (SELECT id FROM counties WHERE slug = 'hampden')      WHERE town IN ('Agawam', 'Blandford', 'Brimfield', 'Chester', 'Chicopee', 'East Longmeadow', 'Granville', 'Hampden', 'Holland', 'Holyoke', 'Longmeadow', 'Ludlow', 'Monson', 'Montgomery', 'Palmer', 'Russell', 'Southwick', 'Springfield', 'Tolland', 'Wales', 'West Springfield', 'Westfield', 'Wilbraham');
UPDATE organizations SET county_id = (SELECT id FROM counties WHERE slug = 'hampshire')    WHERE town IN ('Amherst', 'Belchertown', 'Chesterfield', 'Cummington', 'Easthampton', 'Goshen', 'Granby', 'Hadley', 'Hatfield', 'Huntington', 'Middlefield', 'Northampton', 'Pelham', 'Plainfield', 'South Hadley', 'Southampton', 'Ware', 'Westhampton', 'Williamsburg', 'Worthington');
UPDATE organizations SET county_id = (SELECT id FROM counties WHERE slug = 'middlesex')    WHERE town IN ('Acton', 'Arlington', 'Ashby', 'Ashland', 'Ayer', 'Bedford', 'Belmont', 'Billerica', 'Boxborough', 'Burlington', 'Cambridge', 'Carlisle', 'Chelmsford', 'Concord', 'Dracut', 'Dunstable', 'Everett', 'Framingham', 'Groton', 'Holliston', 'Hopkinton', 'Hudson', 'Lexington', 'Lincoln', 'Littleton', 'Lowell', 'Malden', 'Marlborough', 'Maynard', 'Medford', 'Melrose', 'Natick', 'Newton', 'North Reading', 'Pepperell', 'Reading', 'Sherborn', 'Shirley', 'Somerville', 'Stoneham', 'Stow', 'Sudbury', 'Tewksbury', 'Townsend', 'Tyngsborough', 'Wakefield', 'Waltham', 'Watertown', 'Wayland', 'Westford', 'Weston', 'Wilmington', 'Winchester', 'Woburn');
UPDATE organizations SET county_id = (SELECT id FROM counties WHERE slug = 'nantucket')    WHERE town IN ('Nantucket');
UPDATE organizations SET county_id = (SELECT id FROM counties WHERE slug = 'norfolk')      WHERE town IN ('Avon', 'Bellingham', 'Braintree', 'Brookline', 'Canton', 'Cohasset', 'Dedham', 'Dover', 'Foxborough', 'Franklin', 'Holbrook', 'Medfield', 'Medway', 'Millis', 'Milton', 'Needham', 'Norfolk', 'Norwood', 'Plainville', 'Quincy', 'Randolph', 'Sharon', 'Stoughton', 'Walpole', 'Wellesley', 'Westwood', 'Weymouth', 'Wrentham');
UPDATE organizations SET county_id = (SELECT id FROM counties WHERE slug = 'plymouth')     WHERE town IN ('Abington', 'Bridgewater', 'Brockton', 'Carver', 'Duxbury', 'East Bridgewater', 'Halifax', 'Hanover', 'Hanson', 'Hingham', 'Hull', 'Kingston', 'Lakeville', 'Marion', 'Marshfield', 'Mattapoisett', 'Middleborough', 'Norwell', 'Pembroke', 'Plymouth', 'Plympton', 'Rochester', 'Rockland', 'Scituate', 'Wareham', 'West Bridgewater', 'Whitman');
UPDATE organizations SET county_id = (SELECT id FROM counties WHERE slug = 'suffolk')      WHERE town IN ('Boston', 'Chelsea', 'Revere', 'Winthrop');
UPDATE organizations SET county_id = (SELECT id FROM counties WHERE slug = 'worcester')   WHERE town IN ('Ashburnham', 'Athol', 'Auburn', 'Barre', 'Berlin', 'Blackstone', 'Bolton', 'Boylston', 'Brookfield', 'Charlton', 'Clinton', 'Douglas', 'Dudley', 'East Brookfield', 'Fitchburg', 'Gardner', 'Grafton', 'Hardwick', 'Harvard', 'Holden', 'Hopedale', 'Hubbardston', 'Lancaster', 'Leicester', 'Leominster', 'Lunenburg', 'Mendon', 'Milford', 'Millbury', 'Millville', 'New Braintree', 'North Brookfield', 'Northborough', 'Northbridge', 'Oakham', 'Oxford', 'Paxton', 'Petersham', 'Phillipston', 'Princeton', 'Royalston', 'Rutland', 'Shrewsbury', 'Southborough', 'Southbridge', 'Spencer', 'Sterling', 'Sturbridge', 'Sutton', 'Templeton', 'Upton', 'Uxbridge', 'Warren', 'Webster', 'West Boylston', 'West Brookfield', 'Westborough', 'Westminster', 'Winchendon', 'Worcester');

-- Update schema.sql counterpart
