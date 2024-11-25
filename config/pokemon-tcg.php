<?php

return [

    'tcg_api_key' => getenv('POKEMON_TCG_API_KEY') ?: '525d1586-b5d5-482f-a938-02ac174a7c40',
    'tcg_api_url' => getenv('POKEMON_TCG_API_URL') ?: 'https://api.pokemontcg.io/v2/',

    'tcg-min-price' => [
        [
            "type" => "set",
            "data" => [
                "Call of Legends" => [
                    "Common" => 3.00,
                    "Common Reverse" => 5.00,
                    "Uncommon" => 3.00,
                    "Uncommon Reverse" => 5.00,
                    "Rare" => 5.00,
                    "Rare Holo" => 7.00,
                    "Rare Reverse" => 7.00,
                    "Promo" => 10.00
                ],
            ]
        ],
        // (Ilagay lahat ng data mo dito.)
        [
            "type" => "series",
            "data" => [
                "Scarlet & Violet" => [
                    "Common" => 0.25,
                    "Common Reverse" => 1.00,
                    "Uncommon" => 0.25,
                    "Uncommon Reverse" => 1.00,
                    "Rare" => 1.00,
                    "Rare Reverse" => 2.00,
                    "Promo" => 2.00,
                    "Double Rare" => 2.50,
                    "Ultra Rare" => 5.00,
                    "Illustration Rare" => 5.00,
                    // "Special Illustration Rare" => 1.00,
                    // "Hyper Rare" => 1.00,
                    "Ace Spec Rare" => 7.50,
                    // "Shiny Ultra Rare" => 1.00,
                    // "Shiny Rare" => 1.00
                ],
                "Sword & Shield" => [
                    "Common" => 0.25,
                    "Common Reverse" => 1.00,
                    "Uncommon" => 0.25,
                    "Uncommon Reverse" => 1.00,
                    "Rare" => 1.00,
                    "Rare Holo" => 1.50,
                    "Rare Reverse" => 1.50,
                    "Promo" => 2.00,
                    "Rare Holo V" => 2.50,
                    "Rare Holo Vmax" => 3.50,
                    "Rare Ultra" => 5.00,
                    "Radiant Rare" => 2.00,
                    "Amazing Rare" => 5.00,
                    "Trainer Gallery Rare Holo" => 3.00,
                    "Rare Holo Vstar" => 4.00,
                    // "Rare Rainbow" => 1.00,
                    // "Rare Secret" => 1.00,
                    "Rare Shiny" => 2.50,
                    "Classic Collection" => 2.50
                ],
                "Sun & Moon" => [
                    "Common" => 0.50,
                    "Common Reverse" => 2.00,
                    "Uncommon" => 0.50,
                    "Uncommon Reverse" => 2.00,
                    "Rare" => 2.00,
                    "Rare Holo" => 2.00,
                    "Rare Reverse" => 2.00,
                    "Promo" => 5.00,
                    "Rare Holo GX" => 4.00,
                    "Rare Ultra" => 10.00,
                    "Rare Rainbow" => 10.00,
                    "Rare Secret" => 7.50,
                    "Rare Shiny" => 6.00,
                    "Rare Shiny GX" => 10.00,
                    "Rare Prism Star" => 5.00,
                    "Rare Shining" => 7.00
                ],
                "XY" => [
                    "Common" => 0.50,
                    "Common Reverse" => 2.00,
                    "Uncommon" => 0.50,
                    "Uncommon Reverse" => 2.00,
                    "Rare" => 2.50,
                    "Rare Holo" => 4.00,
                    "Rare Reverse" => 4.00,
                    "Promo" => 5.00,
                    "Rare Break" => 5.00,
                    "Rare Holo EX" => 5.00,
                    "Rare Secret" => 18.00,
                    "Rare Ultra" => 15.00
                ],
                "Black & White" => [
                    "Common" => 2.00,
                    "Common Reverse" => 3.50,
                    "Uncommon" => 2.00,
                    "Uncommon Reverse" => 3.50,
                    "Rare" => 3.50,
                    "Rare Holo" => 4.00,
                    "Rare Reverse" => 4.00,
                    "Promo" => 6.00,
                    "Rare Ace" => 10.00,
                    "Rare Holo EX" => 4.00,
                    // "Rare Secret" => 1.00,
                    "Rare Ultra" => 15.00
                ],
                "HeartGold & SoulSilver" => [
                    "Common" => 2.00,
                    "Common Reverse" => 5.00,
                    "Uncommon" => 2.00,
                    "Uncommon Reverse" => 5.00,
                    "Rare" => 3.00,
                    "Rare Holo" => 6.00,
                    "Rare Reverse" => 6.00,
                    "Promo" => 7.50,
                    // "LEGEND" => 1.00,
                    // "Rare Secret" => 1.00,
                    "Rare Prime" => 15.00,
                    // "Rare Holo LV.X" => 1.00
                ],
                "Platinum" => [
                    "Common" => 2.00,
                    "Common Reverse" => 5.00,
                    "Uncommon" => 2.00,
                    "Uncommon Reverse" => 5.00,
                    "Rare" => 5.00,
                    "Rare Holo" => 7.00,
                    "Rare Reverse" => 7.00,
                    "Rare Holo LV.X" => 30.00,
                    "Rare Secret" => 75.00
                ],
                "Diamond & Pearl" => [
                    "Common" => 2.00,
                    "Common Reverse" => 5.00,
                    "Uncommon" => 2.00,
                    "Uncommon Reverse" => 5.00,
                    "Rare" => 8.00,
                    "Rare Holo" => 10.00,
                    "Rare Reverse" => 10.00,
                    "Promo" => 10.00,
                    "Rare Holo LV.X" => 30.00,
                    // "Rare Secret" => 1.00

                ],
                "EX" => [
                    "Common" => 2.50,
                    "Common Reverse" => 5.00,
                    "Uncommon" => 2.50,
                    "Uncommon Reverse" => 5.00,
                    "Rare" => 8.00,
                    "Rare Holo" => 15.00,
                    "Rare Reverse" => 15.00,
                    "Rare Secret" => 25.00,
                    "Rare Holo EX" => 50.00,
                    "Rare Holo Star" => 250.00
                ],
                "E-Card" => [
                    "Common" => 4.00,
                    "Common Reverse" => 7.00,
                    "Uncommon" => 4.00,
                    "Uncommon Reverse" => 7.00,
                    "Rare" => 10.00,
                    "Rare Holo" => 50.00,
                    "Rare Reverse" => 40.00,
                    "Rare Secret" => 150.00
                ],
                "Legendary Collection" => [
                    "Common" => 5.00,
                    "Common Reverse" => 15.00,
                    "Uncommon" => 5.00,
                    "Uncommon Reverse" => 15.00,
                    "Rare" => 10.00,
                    "Rare Holo" => 25.00,
                    "Rare Reverse" => 35.00,
                    "Promo" => 15.00
                ],

            ],
        ],

        [
            "type"=>"EditionWithSetName",
            "data" => [
                "Base Set 2" => [
                        "normal" => [
                            "Common" => 3.00,
                            "Uncommon" => 3.00,
                            "Rare" => 15.00,
                            "Rare Holo" => 25.00,
                        ],
                ],
                "Team Rocket" => [
                    "1stEdition" => [
                            "Common" => 4.00,
                            "Uncommon" => 4.00,
                            "Rare" => 15.00,
                            "Rare Holo" => 25.00,
                    ],
                    "unlimited" => [
                            "Common" => 2.00,
                            "Uncommon" => 2.00,
                            "Rare" => 10.00,
                            "Rare Holo" => 10.00,
                    ],
                    "1stEditionHolofoil" => [
                            "Common" => 4.00,
                            "Uncommon" => 4.00,
                            "Rare" => 15.00,
                            "Rare Holo" => 25.00,
                    ],
                    "unlimitedHolofoil" => [
                            "Common" => 2.00,
                            "Uncommon" => 2.00,
                            "Rare" => 10.00,
                            "Rare Holo" => 10.00,
                    ],
                ],
                "Fossil" => [
                    "1stEdition" => [
                            "Common" => 3.00,
                            "Uncommon" => 3.00,
                            "Rare" => 15.00,
                            "Rare Holo" => 25.00,
                    ],
                    "unlimited" => [
                            "Common" => 2.00,
                            "Uncommon" => 2.00,
                            "Rare" => 10.00,
                            "Rare Holo" => 10.00,
                    ],
                    "1stEditionHolofoil" => [
                        "Common" => 3.00,
                        "Uncommon" => 3.00,
                        "Rare" => 15.00,
                        "Rare Holo" => 25.00,
                    ],
                    "unlimitedHolofoil" => [
                            "Common" => 2.00,
                            "Uncommon" => 2.00,
                            "Rare" => 10.00,
                            "Rare Holo" => 10.00,
                    ],
                ],
                "Jungle" => [
                    "1stEdition" => [
                            "Common" => 3.00,
                            "Uncommon" => 3.00,
                            "Rare" => 15.00,
                            "Rare Holo" => 25.00,
                    ],
                    "unlimited" => [
                            "Common" => 2.00,
                            "Uncommon" => 2.00,
                            "Rare" => 10.00,
                            "Rare Holo" => 10.00,
                    ],
                    "1stEditionHolofoil" => [
                        "Common" => 3.00,
                        "Uncommon" => 3.00,
                        "Rare" => 15.00,
                        "Rare Holo" => 25.00,
                    ],
                    "unlimitedHolofoil" => [
                            "Common" => 2.00,
                            "Uncommon" => 2.00,
                            "Rare" => 10.00,
                            "Rare Holo" => 10.00,
                    ],
                ],
                "Base" => [
                    "1stEdition" => [
                        "Common" => 10.00,
                        "Uncommon" => 10.00,
                        "Rare" => 25.00,
                        "Rare Holo" => 100.00,
                    ],
                    "unlimited" => [
                        "Common" => 3.00,
                        "Uncommon" => 3.00,
                        "Rare" => 8.00,
                        "Rare Holo" => 15.00,
                    ],
                    "1stEditionHolofoil" => [
                        "Common" => 10.00,
                        "Uncommon" => 10.00,
                        "Rare" => 25.00,
                        "Rare Holo" => 100.00,
                    ],
                    "unlimitedHolofoil" => [
                        "Common" => 3.00,
                        "Uncommon" => 3.00,
                        "Rare" => 8.00,
                        "Rare Holo" => 15.00,
                    ],

                ]
            ],
        ],
        [
            "type" =>"Edition",
            "data" => [
                "Neo" => [
                    "1stEdition" => [
                        "Common" => 6.00,
                        "Uncommon" => 6.00,
                        "Rare" => 15.00,
                        "Rare Holo" => 35.00,
                        "Promo" => 20.00,
                        "Rare Shining" => 250.00,
                    ],
                    "1stEditionHolofoil" => [
                        "Common" => 6.00,
                        "Uncommon" => 6.00,
                        "Rare" => 15.00,
                        "Rare Holo" => 35.00,
                        "Promo" => 20.00,
                        "Rare Shining" => 250.00,
                    ],
                    "unlimited" => [
                        "Common" => 4.00,
                        "Uncommon" => 4.00,
                        "Rare" => 15.00,
                        "Rare Holo" => 25.00,
                        "Promo" => 20.00,
                        "Rare Shining" => 250.00
                    ],
                    "unlimitedHolofoil" => [
                        "Common" => 4.00,
                        "Uncommon" => 4.00,
                        "Rare" => 15.00,
                        "Rare Holo" => 25.00,
                        "Promo" => 20.00,
                        "Rare Shining" => 250.00
                    ],
                ],
                "Gym" => [
                    "1stEdition" => [
                        "Common" => 4.00,
                        "Uncommon" => 4.00,
                        "Rare" => 15.00,
                        "Rare Holo" => 25.00,
                    ],
                    "1stEditionHolofoil" => [
                        "Common" => 4.00,
                        "Uncommon" => 4.00,
                        "Rare" => 15.00,
                        "Rare Holo" => 25.00,
                    ],
                    "unlimited" => [
                        "Common" => 2.00,
                        "Uncommon" => 2.00,
                        "Rare" => 10.00,
                        "Rare Holo" => 10.00,
                    ],
                    "unlimitedHolofoil" => [
                        "Common" => 2.00,
                        "Uncommon" => 2.00,
                        "Rare" => 10.00,
                        "Rare Holo" => 10.00,
                    ],
                ],
            ],
        ],
    ],

    'tcg-edition-names' => [
        'normal' => 'Normal',
        'reverseHolofoil' => 'Reverse Holofoil',
        'holofoil' => 'Holo Foil',
        '1stEdition' => '1st Edition',
        'unlimited' => 'Unlimited',
        'unlimitedHolofoil' => 'Unlimited Holofoil',
        '1stEditionHolofoil' => '1st Edition Holofoil',
    ]
];
