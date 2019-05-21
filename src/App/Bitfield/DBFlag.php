<?php

namespace App\Bitfield;

// Door flags are used when an entity is loaded onto a MapServer after a map transfer, to determine their initial position on the new map. They are cleared immediately after loading.

class DBFlag extends BitField
{
    const DBFLAG_TELEPORT_XFER = 0;             // Door flag. Teleport the entity to the location described by its SpawnTarget field.
    const DBFLAG_UNTARGETABLE = 1;              // Sets the untargetable and admin fields on the entity.
    const DBFLAG_INVINCIBLE = 2;                // Sets the invincible field on the entity.
    const DBFLAG_DOOR_XFER = 3;                 // Door flag. Find the door described by its SpawnTarget field and place the entity there.
    const DBFLAG_MISSION_ENTER = 4;             // Door flag. Place the entity at the MissionStart location.
    const DBFLAG_MISSION_EXIT = 5;              // Door flag. Place the entity at the first location in its MapHistory field, with orientation rotated 180 degrees.
    const DBFLAG_INVISIBLE = 6;                 // Sets the hide field on the entity to 2 (what does this do???).
    const DBFLAG_INTRO_TELEPORT = 7;            // Door flag. Place the entity at the NewPlayer, NewPlayerHero, or NewPlayerVillain location, reset story arcs, and send the new player welcome message.
    const DBFLAG_MAPMOVE = 8;                   // Door flag. Place the entity at a random door on the map.
    const DBFLAG_CLEARATTRIBS = 9;              // All "abusive" buffs will be cleared when this entity is loaded. This is set when the entity is on an arena map, in a raid, an architect task force, or on a pvp map.
    const DBFLAG_NOCOLL = 10;                   // Sets the nocoll field on the entity to 2 (what does this do???).
    const DBFLAG_BASE_ENTER = 11;               // Door flag. Find the spawn location based on its SpawnTarget field, handling base raids appropriately.
    const DBFLAG_RENAMEABLE = 12;               // Set when the entity has a rename token applied, making it possible for the client to rename them.
    const DBFLAG_BASE_EXIT = 13;                // Door flag. If the last location in its MapHistory field is a mission, go to BaseTeleportSpawn. Otherwise, like DBFLAG_MISSION_EXIT.
    const DBFLAG_ALT_CONTACT = 14;              // Indicates that the entity's initial contact is Burke instead of Kalinda.
    const DBFLAG_ARCHITECT_EXIT = 15;           // Unused.
    const DBFLAG_PRAET_SG_JOIN = 16;            // Set when an ex-Praetorian character joins a supergroup, to award their join bonus to only their first supergroup.
    const DBFLAG_HALF_MAX_HEALTH = 17;          // Halves the max health of the character, until removed by a special reward. Comments indicate this was used during the Praetorian tutorial, but now appears to be obsolete.
    const DBFLAG_UNLOCK_HERO_EPICS = 18;        // Unlocks Kheldian characters, if any character on the account is at least level 20 and has this flag set. Will be set for Primal Hero characters.
    const DBFLAG_UNLOCK_VILLAIN_EPICS = 19;     // Unlocks Arachnos characters, if any character on the account is at least level 20 and has this flag set. Will be set for Primal Villain characters.
}
