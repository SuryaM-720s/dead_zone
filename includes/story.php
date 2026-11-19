<?php
/**
 * story.php
 * The whole maze as a branching story tree (a nested PHP array).
 *
 * Each node = one junction in the maze:
 *   'title'   => heading shown to the player
 *   'area'    => map region (used for the explored-area map)
 *   'text'    => paragraphs of story text
 *   'horde'   => optional ['noise' => N, 'to' => node]: if the player arrives
 *                with Noise >= N, the horde forces them to node 'to' instead
 *   'ending'  => optional ending key; arriving here finishes the run
 *   'choices' => list of choices, each with:
 *       'text'      button label
 *       'next'      node id this choice leads to
 *       'effects'   stat changes, e.g. ['noise' => 2, 'stamina' => -10]
 *       'requires'  gates: stat minimums, 'noise_max', 'item', 'class'
 *       'gives'     items added to the inventory
 *       'uses'      items consumed
 *       'survivor'  survivors rescued by this choice
 *       'bite_risk' you are bitten if your Luck is BELOW this number
 *       'result'    narrator line shown after the choice
 */

function story_nodes(): array
{
    return [

        // ───────────────────────── THE HALL ─────────────────────────
        'start' => [
            'title' => 'Lecture Hall 204',
            'area'  => 'hall',
            'text'  => [
                "The fire alarm has been screaming for six minutes. Nobody is coming to turn it off.",
                "Through the window, the street below is chaos: abandoned cars, a bus on its side, people running in every direction. Some of them are running wrong, lurching, too fast for how broken they look.",
                "Your phone buzzes once before the network dies: EVACUATION FLIGHTS DEPART THE STADIUM LOT AT DAWN. That's two miles south, through all of it.",
            ],
            'choices' => [
                [
                    'text' => 'Take the main stairwell down',
                    'next' => 'stairwell',
                    'effects' => ['noise' => 1],
                    'result' => "Your footsteps ring off the concrete. Stairwells carry sound.",
                ],
                [
                    'text' => 'Search the chemistry lab across the hall',
                    'next' => 'lab',
                    'effects' => ['stamina' => -5],
                    'result' => "You duck across the hall. Labs keep emergency kits.",
                ],
                [
                    'text' => 'Climb out the window onto the ledge',
                    'next' => 'ledge',
                    'requires' => ['stamina' => 40],
                    'effects' => ['stamina' => -10],
                    'result' => "You swing a leg over the sill. The wind hits hard four floors up.",
                ],
            ],
        ],

        'ledge' => [
            'title' => 'The Fourth-Floor Ledge',
            'area'  => 'hall',
            'text'  => [
                "The ledge is barely a foot wide. Below, a dozen of them shuffle in circles around a crashed delivery truck.",
                "The library roof is a six-foot gap away. Nothing down there would hear you take it.",
            ],
            'choices' => [
                [
                    'text' => 'Jump the gap to the library roof',
                    'next' => 'library',
                    'requires' => ['stamina' => 40],
                    'effects' => ['stamina' => -15, 'noise' => -1],
                    'result' => "You land hard, roll, and come up on gravel. Silent. Nothing below even looks up.",
                ],
                [
                    'text' => 'Lose your nerve and climb back inside',
                    'next' => 'stairwell',
                    'effects' => ['noise' => 1],
                    'result' => "You scramble back through the window and take the stairs like everyone else.",
                ],
            ],
        ],

        'lab' => [
            'title' => 'Chemistry Lab',
            'area'  => 'hall',
            'text'  => [
                "Broken glass, a tipped fume hood, and a red emergency cabinet on the far wall, still sealed.",
                "Something is thumping inside the supply closet. Slow. Patient. A lanyard is caught in the door, with a med-kit keychain dangling from it.",
            ],
            'choices' => [
                [
                    'text' => 'Take the emergency kit and head for the stairwell',
                    'next' => 'stairwell',
                    'gives' => ['flare', 'flashlight'],
                    'result' => "Inside the cabinet: a road flare and a heavy flashlight. You take both.",
                ],
                [
                    'text' => 'Open the thumping closet and grab the med kit',
                    'next' => 'stairwell',
                    'effects' => ['health' => -15, 'noise' => 2],
                    'bite_risk' => 45,
                    'gives' => ['antibiotics'],
                    'result' => "The thing in the closet used to be a TA. You wrestle the med kit free and slam the door on its arm.",
                ],
                [
                    'text' => 'Kick out the window and take the fire escape',
                    'next' => 'courtyard',
                    'effects' => ['noise' => 3],
                    'result' => "Glass explodes onto the plaza. Every head down there turns toward you.",
                ],
            ],
        ],

        'stairwell' => [
            'title' => 'East Stairwell',
            'area'  => 'hall',
            'text'  => [
                "Emergency lights paint the stairwell red.",
                "Two floors down, a student in a torn hoodie is slumped against the rail, clutching his leg. \"It's not a bite,\" he says, too fast. \"I swear it's not a bite.\"",
            ],
            'choices' => [
                [
                    'text' => 'Help him down the stairs',
                    'next' => 'courtyard',
                    'effects' => ['stamina' => -10, 'noise' => 1],
                    'survivor' => 1,
                    'result' => "His name is Marcus. He's heavy and slow, but he knows every shortcut on campus.",
                ],
                [
                    'text' => 'Examine his leg before you decide',
                    'next' => 'courtyard',
                    'requires' => ['class' => 'medic'],
                    'survivor' => 1,
                    'result' => "A clean fracture from a fall. No bite. You splint it in two minutes and he can walk on his own.",
                ],
                [
                    'text' => 'Step past him and slip out the side door',
                    'next' => 'courtyard',
                    'effects' => ['noise' => -1],
                    'result' => "You don't look back. The side door closes on his voice.",
                ],
            ],
        ],

        // ───────────────────────── THE PLAZA ─────────────────────────
        'courtyard' => [
            'title' => 'The Plaza',
            'area'  => 'plaza',
            'text'  => [
                "The plaza is a field of dropped backpacks and spilled coffee. Walkers drift between the planters, slow until something makes a sound.",
                "Three ways out: the library to the west, the Student Center straight ahead, and the parking deck across open ground.",
            ],
            'choices' => [
                [
                    'text' => 'Cut through the library',
                    'next' => 'library',
                    'effects' => ['noise' => -1],
                    'result' => "Libraries are built to be quiet. You slip inside.",
                ],
                [
                    'text' => 'Raid the Student Center for supplies',
                    'next' => 'foodcourt',
                    'result' => "You weave between the planters toward the Student Center doors.",
                ],
                [
                    'text' => 'Sprint across open ground to the parking deck',
                    'next' => 'deck',
                    'effects' => ['noise' => 2, 'stamina' => -10],
                    'result' => "You run flat out. Behind you, a dozen heads swing around to follow the sound.",
                ],
            ],
        ],

        // ───────────────────────── THE LIBRARY ─────────────────────────
        'library' => [
            'title' => 'University Library',
            'area'  => 'library',
            'text'  => [
                "Rows of overturned chairs. A laptop still glowing on a desk, mid-essay.",
                "Someone is breathing under the circulation desk: a girl holding a stapler like a weapon. Behind her is a locked door marked CAMPUS SECURITY.",
            ],
            'choices' => [
                [
                    'text' => 'Pick the lock on the security office',
                    'next' => 'security',
                    'requires' => ['item' => 'lockpick'],
                    'result' => "Thirty seconds of careful work and the lock clicks open.",
                ],
                [
                    'text' => 'Convince the girl to come with you',
                    'next' => 'deck',
                    'effects' => ['noise' => 1],
                    'survivor' => 1,
                    'result' => "Her name is Priya. She keeps the stapler. You cross the skybridge to the parking deck together.",
                ],
                [
                    'text' => 'Leave quietly over the skybridge',
                    'next' => 'deck',
                    'effects' => ['noise' => -1],
                    'result' => "You cross the glass skybridge on your toes, watching the street below.",
                ],
            ],
        ],

        'security' => [
            'title' => 'Campus Security Office',
            'area'  => 'library',
            'text'  => [
                "The camera monitors are still on. The parking deck ramp is packed with walkers drawn by noise. The entrance to Five Points station looks clear.",
                "On a hook by the door: a keycard labelled DOWNTOWN MEDICAL / EMERGENCY STAFF ACCESS.",
            ],
            'choices' => [
                [
                    'text' => 'Take the keycard and head down to Five Points station',
                    'next' => 'marta',
                    'gives' => ['keycard'],
                    'effects' => ['noise' => -1],
                    'result' => "You pocket the keycard and follow the clear route the cameras showed you.",
                ],
                [
                    'text' => 'Take the keycard and cross to the parking deck',
                    'next' => 'deck',
                    'gives' => ['keycard'],
                    'result' => "Keycard in hand, you head for the skybridge despite what the cameras showed.",
                ],
            ],
        ],

        // ───────────────────────── STUDENT CENTER ─────────────────────────
        'foodcourt' => [
            'title' => 'Student Center Food Court',
            'area'  => 'student_center',
            'text'  => [
                "The food court smells like burnt grease. One walker in a cashier's apron stands behind the register, swaying, facing the wall.",
                "The convenience store shutters are half up. A maintenance closet sits beside the restrooms.",
            ],
            'choices' => [
                [
                    'text' => 'Grab bandages and water from the store',
                    'next' => 'marta',
                    'gives' => ['bandages'],
                    'effects' => ['noise' => 2, 'stamina' => 10],
                    'result' => "The shutter screeches as you squeeze under it. You grab what you can and head for the station.",
                ],
                [
                    'text' => 'Pry open the maintenance closet',
                    'next' => 'marta',
                    'gives' => ['lockpick'],
                    'effects' => ['stamina' => -10],
                    'result' => "Inside: a janitor's ring of picks and tension wrenches. You take the set and head for the station.",
                ],
                [
                    'text' => 'Take down the walker by the register',
                    'next' => 'deck',
                    'effects' => ['health' => -20],
                    'bite_risk' => 40,
                    'gives' => ['energy_bar'],
                    'result' => "It's a messy fight. Its pockets hold an energy bar. You leave through the back toward the parking deck.",
                ],
            ],
        ],

        // ───────────────────────── PARKING DECK ─────────────────────────
        'deck' => [
            'title' => 'Parking Deck, Level 3',
            'area'  => 'deck',
            'horde' => ['noise' => 5, 'to' => 'deck_horde'],
            'text'  => [
                "Rows of dead cars. The ramp spirals down to the street, and a stairwell leads to the Five Points station entrance.",
                "A delivery van sits with its side door open. It's dark and quiet in there.",
            ],
            'choices' => [
                [
                    'text' => 'Walk down the ramp to the street',
                    'next' => 'street',
                    'result' => "You keep to the inside wall all the way down the spiral.",
                ],
                [
                    'text' => 'Hide in the van until things go quiet',
                    'next' => 'street',
                    'effects' => ['noise' => -3, 'stamina' => 10],
                    'result' => "You wait in the dark for an hour. Whatever was following you loses interest.",
                ],
                [
                    'text' => 'Take the stairwell down to Five Points station',
                    'next' => 'marta',
                    'result' => "The stairwell door groans. You ease it shut behind you.",
                ],
            ],
        ],

        'deck_horde' => [
            'title' => 'Swarmed on Level 3',
            'area'  => 'deck',
            'text'  => [
                "You made too much noise. They followed you up the ramp, dozens of them, filling level 3 from wall to wall.",
                "The stairwell is twenty yards away. The ramp is solid bodies.",
            ],
            'choices' => [
                [
                    'text' => 'Light a flare and hurl it down the ramp',
                    'next' => 'street',
                    'requires' => ['item' => 'flare'],
                    'uses' => ['flare'],
                    'effects' => ['noise' => -10],
                    'result' => "Red light floods the ramp. Every one of them turns to follow it. You walk out behind them.",
                ],
                [
                    'text' => 'Fight your way through to the ramp',
                    'next' => 'street',
                    'effects' => ['health' => -35, 'noise' => 1],
                    'bite_risk' => 50,
                    'result' => "You swing at anything that moves and come out the bottom of the ramp shaking.",
                ],
                [
                    'text' => 'Break for the stairwell to the station',
                    'next' => 'marta',
                    'requires' => ['stamina' => 20],
                    'effects' => ['stamina' => -15],
                    'result' => "You sprint the twenty yards and slam the stairwell door. Fists pound on it behind you.",
                ],
            ],
        ],

        // ───────────────────────── FIVE POINTS ─────────────────────────
        'marta' => [
            'title' => 'Five Points Station',
            'area'  => 'marta',
            'text'  => [
                "Past the turnstiles the station is pitch black. Somewhere down the tracks, water drips.",
                "The southbound tunnel runs under the city toward Downtown Medical's basement, if you can see where you're going.",
            ],
            'choices' => [
                [
                    'text' => 'Switch on your flashlight and walk the tunnel',
                    'next' => 'hospital_basement',
                    'requires' => ['item' => 'flashlight'],
                    'effects' => ['noise' => -1],
                    'result' => "The beam picks out rails, rats and one walker pinned under a fallen sign. You step around it.",
                ],
                [
                    'text' => 'Feel your way through the dark',
                    'next' => 'hospital_basement',
                    'effects' => ['health' => -15],
                    'bite_risk' => 35,
                    'result' => "You trip twice, cut your hands on the rail, and feel something brush your ankle.",
                ],
                [
                    'text' => 'Climb back up to the street',
                    'next' => 'street',
                    'result' => "You take the escalator up, two steps at a time.",
                ],
            ],
        ],

        // ───────────────────────── DOWNTOWN ─────────────────────────
        'street' => [
            'title' => 'Downtown Streets',
            'area'  => 'downtown',
            'text'  => [
                "Abandoned cars, blocks of broken glass. To the south you can see the stadium floodlights.",
                "From a dead police cruiser, a radio crackles: \"...anyone on this channel... Downtown Medical's radio room still has power...\"",
            ],
            'choices' => [
                [
                    'text' => 'Sprint straight for the stadium',
                    'next' => 'stadium_gate',
                    'requires' => ['stamina' => 35],
                    'effects' => ['stamina' => -20, 'noise' => 2],
                    'result' => "Two miles at a dead run. Your lungs burn. Things in the alleys turn to follow.",
                ],
                [
                    'text' => 'Creep south along the rail line',
                    'next' => 'stadium_gate',
                    'effects' => ['noise' => -2, 'stamina' => -5],
                    'result' => "The rail cut is slow and muddy, but nothing down there hears you.",
                ],
                [
                    'text' => 'Head into Downtown Medical',
                    'next' => 'hospital_lobby',
                    'result' => "You follow the sirens to the hospital's front doors.",
                ],
            ],
        ],

        // ───────────────────────── HOSPITAL ─────────────────────────
        'hospital_lobby' => [
            'title' => 'Downtown Medical Lobby',
            'area'  => 'hospital',
            'text'  => [
                "Gurneys are stacked against the doors as a barricade that didn't hold.",
                "Signs point three ways: PHARMACY (locked), EMERGENCY, and STAFF STAIRS (keycard only).",
            ],
            'choices' => [
                [
                    'text' => 'Pick the pharmacy lock',
                    'next' => 'pharmacy',
                    'requires' => ['item' => 'lockpick'],
                    'result' => "The pharmacy lock is better than the library's, but not by much.",
                ],
                [
                    'text' => 'Check the ER for survivors',
                    'next' => 'er',
                    'effects' => ['noise' => 1],
                    'result' => "You push through the double doors into the emergency room.",
                ],
                [
                    'text' => 'Badge into the staff stairs',
                    'next' => 'radio',
                    'requires' => ['item' => 'keycard'],
                    'effects' => ['stamina' => -10],
                    'result' => "The reader blinks green. Nine flights up, you reach the radio room.",
                ],
            ],
        ],

        'hospital_basement' => [
            'title' => 'Hospital Basement',
            'area'  => 'hospital',
            'text'  => [
                "You come up through a maintenance hatch into the hospital basement. Generators hum somewhere nearby.",
                "The pharmacy's back door is propped open with a fire extinguisher.",
            ],
            'choices' => [
                [
                    'text' => 'Slip into the pharmacy through the back',
                    'next' => 'pharmacy',
                    'result' => "Nobody locks a door they've propped open.",
                ],
                [
                    'text' => 'Take the service elevator up to the lobby',
                    'next' => 'hospital_lobby',
                    'effects' => ['noise' => 1],
                    'result' => "The elevator chimes at the top. Loudly.",
                ],
            ],
        ],

        'pharmacy' => [
            'title' => 'Hospital Pharmacy',
            'area'  => 'hospital',
            'text'  => [
                "The shelves are half looted, but the locked cage in the back is untouched: antibiotics, gauze, everything you need.",
            ],
            'choices' => [
                [
                    'text' => 'Load up, then take the staff stairs to the radio room',
                    'next' => 'radio',
                    'requires' => ['item' => 'keycard'],
                    'gives' => ['antibiotics', 'bandages'],
                    'effects' => ['stamina' => -10],
                    'result' => "You fill your bag and climb nine floors to the radio room.",
                ],
                [
                    'text' => 'Load up and check the ER',
                    'next' => 'er',
                    'gives' => ['antibiotics', 'bandages'],
                    'result' => "Bag full, you head down the corridor toward the emergency room.",
                ],
                [
                    'text' => 'Load up and head for the stadium',
                    'next' => 'stadium_gate',
                    'gives' => ['antibiotics', 'bandages'],
                    'effects' => ['stamina' => -10],
                    'result' => "You leave through the ambulance bay and head south.",
                ],
            ],
        ],

        'er' => [
            'title' => 'Emergency Room',
            'area'  => 'hospital',
            'text'  => [
                "A nurse in blood-streaked scrubs has barricaded herself behind the triage desk with a fire axe.",
                "\"I'm not going out there without a plan,\" she says.",
            ],
            'choices' => [
                [
                    'text' => 'Break through her barricade and bring her with you',
                    'next' => 'stadium_gate',
                    'requires' => ['stamina' => 20],
                    'effects' => ['stamina' => -20, 'noise' => 1],
                    'survivor' => 1,
                    'result' => "Her name is Dana. She's handy with the axe.",
                ],
                [
                    'text' => 'Take her up to the radio room with your keycard',
                    'next' => 'radio',
                    'requires' => ['item' => 'keycard'],
                    'survivor' => 1,
                    'result' => "Dana knows the building. She gets you to the radio room by the quiet stairs.",
                ],
                [
                    'text' => 'Let her treat your wounds, then leave alone',
                    'next' => 'stadium_gate',
                    'effects' => ['health' => 30],
                    'result' => "She stitches you up without a word. When you leave, she's barricading again.",
                ],
            ],
        ],

        'radio' => [
            'title' => 'Radio Room, 9th Floor',
            'area'  => 'hospital',
            'text'  => [
                "The radio room still runs on backup power. Through the window you can see the whole city, and the stadium lights to the south.",
                "You could broadcast the safe route (tunnels, skybridge, hospital) to every survivor still listening. It will take time, and anything following you will catch up.",
            ],
            'choices' => [
                [
                    'text' => 'Broadcast the safe route to the city',
                    'next' => 'ending_signal',
                    'requires' => ['noise_max' => 4],
                    'result' => "You key the mic and start talking.",
                ],
                [
                    'text' => 'Skip the broadcast and run for the stadium',
                    'next' => 'stadium_gate',
                    'effects' => ['stamina' => -10],
                    'result' => "No time. You take the stairs back down and head south.",
                ],
            ],
        ],

        // ───────────────────────── STADIUM ─────────────────────────
        'stadium_gate' => [
            'title' => 'Stadium Lot',
            'area'  => 'stadium',
            'horde' => ['noise' => 6, 'to' => 'gate_horde'],
            'text'  => [
                "Floodlights. A chain-link fence. On the other side, a helicopter with its rotors already turning.",
                "A soldier on the far side waves you on. \"Thirty seconds!\"",
            ],
            'choices' => [
                [
                    'text' => 'Climb the fence',
                    'next' => 'ending_extraction',
                    'requires' => ['stamina' => 30],
                    'effects' => ['stamina' => -15],
                    'result' => "You go over the top and drop into the soldier's arms.",
                ],
                [
                    'text' => 'Squeeze through the broken ticket gate',
                    'next' => 'ending_extraction',
                    'effects' => ['health' => -20],
                    'result' => "The jagged metal tears your side open, but you're through.",
                ],
            ],
        ],

        'gate_horde' => [
            'title' => 'The Lot Is Swarming',
            'area'  => 'stadium',
            'text'  => [
                "All the noise you made tonight caught up with you. Hundreds of them stand between you and the fence.",
                "The helicopter will not wait.",
            ],
            'choices' => [
                [
                    'text' => 'Light a flare and throw it away from the chopper',
                    'next' => 'ending_extraction',
                    'requires' => ['item' => 'flare'],
                    'uses' => ['flare'],
                    'effects' => ['noise' => -10],
                    'result' => "The horde turns as one toward the red light. You walk straight to the fence.",
                ],
                [
                    'text' => 'Fight your way to the chopper',
                    'next' => 'ending_extraction',
                    'effects' => ['health' => -40],
                    'bite_risk' => 50,
                    'result' => "You don't remember the last fifty yards. Just hands pulling you aboard.",
                ],
            ],
        ],

        // ───────────────────────── ENDING NODES ─────────────────────────
        'ending_extraction' => [
            'title' => 'Extraction',
            'area'  => 'stadium',
            'ending' => 'extraction',
            'text' => [],
            'choices' => [],
        ],
        'ending_signal' => [
            'title' => 'The Signal',
            'area'  => 'hospital',
            'ending' => 'signal',
            'text' => [],
            'choices' => [],
        ],
    ];
}

/**
 * Ending screens. 'turned' and 'overrun' are triggered by stats
 * (infection / health), not by a node.
 */
function ending_defs(): array
{
    return [
        'extraction' => [
            'label' => 'Extraction',
            'type'  => 'heroic',
            'base'  => 500,
            'text'  => "The helicopter lifts off and downtown falls away beneath you. You made it out.",
        ],
        'signal' => [
            'label' => 'The Signal',
            'type'  => 'secret',
            'base'  => 800,
            'text'  => "Your voice goes out on every frequency. By sunrise, survivors are moving through the tunnels on the route you gave them, and a helicopter is setting down on the hospital roof to collect you.",
        ],
        'turned' => [
            'label' => 'Turned',
            'type'  => 'tragic',
            'base'  => 100,
            'text'  => "The fever burns through you. Your hands stop listening. The last thing you remember is the sound of the helicopter, very far away.",
        ],
        'overrun' => [
            'label' => 'Overrun',
            'type'  => 'tragic',
            'base'  => 50,
            'text'  => "There are too many of them and you have nothing left to fight with. The city takes one more.",
        ],
    ];
}
