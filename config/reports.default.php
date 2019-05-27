<?php

// How to create custom reports for yourself:
// Make a file called 'reports.user.php' with entries similar to what's below.
// Replacement strings: ':ACCOUNT_NAME', ':CHARACTER_NAME', ':ACCOUNT_UID', ':CHARACTER_CID'

$reports['CharacterSalvage']['description'] = 'Display all salvage a character possesses in their inventory and storage; does not include auction house or game mails.';
$reports['CharacterSalvage']['sql'] = "
SELECT 'Inventory' as Type, *
	FROM cohdb.dbo.InvSalvage0 WHERE ContainerId = :CHARACTER_CID
UNION
SELECT 'Stored' AS Type, *, NULL AS S_ExperiementalTech,
	NULL AS S_UnknownChemicals,
	NULL AS S_SignatureSalvage,
	NULL AS S_SignatureSalvageU,
	NULL AS S_Never_MeltingIce
	FROM cohdb.dbo.InvStoredSalvage0 WHERE ContainerId = :CHARACTER_CID"; // 415 columns(!!!)
$reports['CharacterSalvage']['transpose'] = true;   // We definitely want this report rotated.

$reports['Merits']['description'] = 'Display end game merits held by accounts.';
$reports['Merits']['sql'] = '
SELECT  Ents.AuthName, Ents.Name,
		InvSalvage0.S_EndgameMerit01 + InvStoredSalvage0.S_EndgameMerit01 AS EndgameMerit01,
		InvSalvage0.S_EndgameMerit02 + InvStoredSalvage0.S_EndgameMerit02 AS EndgameMerit02,
        InvSalvage0.S_EndgameMerit03 + InvStoredSalvage0.S_EndgameMerit03 AS EndgameMerit03,
		InvSalvage0.S_EndgameMerit04 + InvStoredSalvage0.S_EndgameMerit04 AS EndgameMerit04,
		InvSalvage0.S_EndgameMerit05 + InvStoredSalvage0.S_EndgameMerit05 AS EndgameMerit05
FROM    Ents
		LEFT JOIN InvSalvage0 ON Ents.ContainerId = InvSalvage0.ContainerId
		LEFT JOIN InvStoredSalvage0 ON Ents.ContainerId = InvStoredSalvage0.ContainerId
WHERE
		InvSalvage0.S_EndgameMerit01 IS NOT NULL OR
		InvSalvage0.S_EndgameMerit02 IS NOT NULL OR
		InvSalvage0.S_EndgameMerit03 IS NOT NULL OR
		InvSalvage0.S_EndgameMerit04 IS NOT NULL OR
		InvSalvage0.S_EndgameMerit05 IS NOT NULL OR
		InvStoredSalvage0.S_EndgameMerit01 IS NOT NULL OR
		InvStoredSalvage0.S_EndgameMerit02 IS NOT NULL OR
		InvStoredSalvage0.S_EndgameMerit03 IS NOT NULL OR
		InvStoredSalvage0.S_EndgameMerit04 IS NOT NULL OR
		InvStoredSalvage0.S_EndgameMerit05 IS NOT NULL
ORDER BY AuthName, Name';
