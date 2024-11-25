<?php
set_time_limit(300); // Para hindi mag-timeout kung malaki ang data
require_once('vendor/autoload.php');
require_once('CommonHelper.php');
use \BenMajor\ExchangeRatesAPI\ExchangeRatesAPI;
use \BenMajor\ExchangeRatesAPI\Exception;

function config($key) {
    static $config;

    if (!$config) {
        $config = array_merge(
            include __DIR__ . '/config/pokemon-tcg.php',
            include __DIR__ . '/config/shopify.php'
        );
    }

    $keys = explode('.', $key);
    $value = $config;

    foreach ($keys as $keyPart) {
        if (isset($value[$keyPart])) {
            $value = $value[$keyPart];
        } else {
            return null;
        }
    }

    return $value;
}

function logToFile($message) {
    $logFile = __DIR__ . '/pokemon.log';
    $logFileCurrency = __DIR__ . '/currency_converter.log';

    $timeStamp = date('Y-m-d H:i:s');
    $formattedMessage = "[{$timeStamp}] {$message}\n";

    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    file_put_contents($logFileCurrency, $formattedMessage, FILE_APPEND);
}

class TcgPokemonAPI {
    private $shopify;
    private $db;

    public function __construct($config, $db) {
        $this->shopifyConfig = $config;
        PHPShopify\ShopifySDK::config($config);
        $this->shopify = new PHPShopify\ShopifySDK();
        $this->db = $db;
        $this->commonHelper = new CommonHelper();
    }

    // Get Data mula sa Pokémon TCG API
    public function GetCardsAPI() {
        $url = "https://api.pokemontcg.io/v2/cards/";

        $response = file_get_contents($url);

        if ($response === FALSE) {
            echo "Error fetching data from API.";
            return;
        }

        $data = json_decode($response, true);

        if (isset($data['data'])) {
            $validCards = array_filter($data['data'], [$this, 'isValidCard']);
            if (empty($validCards)) {
                echo "No valid card data found.";
                return;
            }

            foreach ($validCards as $card) {
                $this->postProductCardDBAndShopify($card);
            }
            
        } else {
            echo "No data found.";
        }
    }

    public function isValidCard($card) {
        return isset($card['name'], $card['images']['small'], $card['set']['name']) &&
               !empty($card['name']) &&
               !empty($card['images']['small']) &&
               !empty($card['set']['name']);
    }

    // Post Card Product And Collection
    public function postProductCardDBAndShopify($cardData) {
        $shopUrl = "18d497-2.myshopify.com";
        $accessToken = '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'; 

        $shopify = new \PHPShopify\ShopifySDK([
            'ShopUrl' => $shopUrl,
            'AccessToken' => $accessToken,
        ]);

        $variants = [];
        $optionsEditionValues = [];
        $optionsConditionValues = ["Near Mint", "Lightly Played", "Played"];
        $conditionDiscounts = [0, 10, 20];

        $prices = [];

        if (isset($card['tcgplayer'])) {
            $priceFormat = $card['tcgplayer'];
        } else {
            $priceFormat = 'No price available';
        }

        if (isset($priceFormat['prices'])) {
            $prices = $priceFormat['prices'];
        } else {
            // tawagin ang getPrices na function
            $prices = $this->getPrices($cardData);
        }

        $editionNames = config('tcg-edition-names');

        $position = 1;

        foreach ($prices as $edition => $priceData) {
            $isSheetPrice = 0;

            if ($this->getCardMinimumPriceAsPerClientRequest($cardData, $edition) != null) {
                $isSheetPrice = 1;
                $priceData['market'] = $this->getCardMinimumPriceAsPerClientRequest($cardData, $edition);
            } elseif (($priceData['market'] == 0 || $priceData['market'] == null) && isset($priceData['low'])) {
                $priceData['market'] = $priceData['low'];
            } elseif ($priceData['market'] == 0 || $priceData['market'] == null) {
                $priceData['market'] = 1.00;
            }

            if (isset($priceData['market'])) {
                $editionTitle = isset($editionNames[$edition]) ? $editionNames[$edition] : ucfirst($edition);

                if (str_contains($editionTitle, 'Holo Foil') || str_contains($editionTitle, 'Edition')) {
                    $editionTitle = $cardData['rarity'];
                }

                if (!in_array($editionTitle, $optionsEditionValues)) {
                    $optionsEditionValues[] = $editionTitle;
                }

                foreach ($optionsConditionValues as $index => $condition) {
                    $discount = $conditionDiscounts[$index];
                    $convertPrice = $priceData['market'];
                    if (!$isSheetPrice) {
                        $convertedPrice = $this->currencyConverter($priceData['market']);
                    }
                    $price = $this->priceConvertForVariant($convertedPrice, $discount);
                    $variants[] = [
                        "option1" => $editionTitle,
                        "option2" => $condition,
                        "price" => $price,
                        "original_price" => $priceData['market'],
                        "sku" => $cardData['id'],
                        "position" => $position,
                        "inventory_management" => "shopify",
                        "inventory_quantity" => 1
                    ];

                    $position++;

                    sleep(1);
                }
            }
        }

        $options = [
            ["name" => "Type",  "values" => array_unique($optionsEditionValues)],
            ["name" => "Condition", "values" => array_unique($optionsConditionValues)],
        ];

        $cardType = $cardData['types'][0];
        $image = $cardData['images'];
        $imageSmall = $image['small'];
        $productTitle = $cardData['name'] . '-' . $cardData['rarity'];
        $cardType = !empty($cardData['types']) ? implode(',', $cardData['types']) : null;
        $productData = [
            'title' => $productTitle,
            'body_html' => $this->convertDescription($cardData),
            'vendor' => 'Misty\'s Collectables',
            'product_type' => 'Pokemon',
            'images' => [
                [
                    'src' => $imageSmall,
                    'alt' => $cardData['name']
                ]
            ],
            'tags' => 'Pokemon, Pokemon TCG,' . $cardType,
            'variants' => $variants,
            'options' => $options,
            "published_scope" => "web",
            "published" => true
        ];

        try {
            
            $products = $shopify->Product->get(['title' => $productTitle]);
            
            if (isset($products) && !empty($products)) {
                $productIDAPI = $products[0]['id'];
                $response = $shopify->Product($productIDAPI)->put($productData);
                echo "Product Successfully Updated";
            } else {

                $pokemonSetCollection = $cardData['set']['name'] ?? 'Pokemon TCG Card';
                $handle = 'pokemon-tcg-collection';
                $pokemonSet = [
                    'name' => $pokemonSetCollection,
                    'images' => [
                        'logo' => 'https://images.pokemontcg.io/base4/logo.png',
                        'alt' => $pokemonSetCollection,
                    ],
                ];

                $collectionId = $this->createOrUpdateShopifyCollection($shopify, $handle, $pokemonSet, $cardData);
                if (!$collectionId) {
                    echo "Failed to create or update collection.\n";
                    return;
                }
                $response = $shopify->Product->post($productData);
                $productId = $response['id'];

                $this->addProductToCollection($shopify, $collectionId, $productId);

                echo "The Cards is Syncing in Shopify API...";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function addProductToCollection($shopify, $collectionId, $productId) {
        if (empty($collectionId) || empty($productId)) {
            echo "Error: Collection ID or Product ID is missing.\n";
            return;
        }
    
        $shopifyConfig = [
            'ShopUrl' => '18d497-2.myshopify.com',
            'AccessToken' => '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',
        ];
    
        $url = "https://{$shopifyConfig['ShopUrl']}/admin/api/2024-10/collects.json";
    
        $data = [
            'collect' => [
                'product_id' => $productId,
                'collection_id' => $collectionId,
            ]
        ];
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "X-Shopify-Access-Token: {$shopifyConfig['AccessToken']}",
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
        $response = curl_exec($ch);
    
        curl_close($ch);
    
        $responseData = json_decode($response, true);
    
        if (isset($responseData['collect'])) {
            echo "Product successfully added to the collection.\n";
        } else {
            echo "Error adding product to collection: " . $response . "\n";
        }
    }

    public function CreateOrUpdateShopifyCollection($shopify, $handle, $pokemonSet, $cardData) {
        $shopifyConfig = [
            'ShopUrl' => '18d497-2.myshopify.com', // Your Shopify store URL
            'AccessToken' => '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', // Your valid Access Token
        ];

        if (empty($pokemonSet['name'])) {
            echo "Error: Collection name is required.\n";
            return false;
        }

        if (empty($pokemonSet['images']['logo'])) {
            echo "Error: Collection image URL is missing.\n";
            return false;
        }

        echo "Collection name: " . $pokemonSet['name'] . "\n";
        echo "Image URL: " . $pokemonSet['images']['logo'] . "\n";

        $imageUrl = $pokemonSet['images']['logo'];
        if (!@getimagesize($imageUrl)) {
            echo "Invalid image URL: $imageUrl\n";
            return false;
        }

        // Shopify API configuration
        $shopUrl = "https://{$shopifyConfig['ShopUrl']}/admin/api/2024-10/custom_collections.json"; // Corrected access
        $accessToken = $shopifyConfig['AccessToken'];  // Corrected access

        // Use cURL to check existing collections
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $shopUrl);  // Correct endpoint for custom collections
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "X-Shopify-Access-Token: $accessToken",  // Authentication using Access Token
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        // Decode the response
        $existingCollections = json_decode($response, true);

        // Check if there was an error decoding the response
        if (is_null($existingCollections)) {
            echo 'Error decoding JSON response.<br>';
            echo 'Response: ' . $response . '<br>';  // Print raw response for debugging
            return false;
        }

        // Search for existing collection by handle
        $existingCollection = null;
        foreach ($existingCollections['custom_collections'] as $collection) {
            if ($collection['handle'] === $handle) {
                $existingCollection = $collection;
                break;
            }
        }

        // If collection exists, update it, else create a new collection
        if ($existingCollection) {
            echo "Collection found. Updating...\n";
            $collectionId = $existingCollection['id'];  // Get the collection ID
            $url = "https://{$shopifyConfig['ShopUrl']}/admin/api/2024-10/custom_collections/{$collectionId}.json";  // URL for update
            $method = 'PUT';  // Update method
        } else {
            echo "Collection not found. Creating...\n";
            $url = "https://{$shopifyConfig['ShopUrl']}/admin/api/2024-10/custom_collections.json";  // URL for creation
            $method = 'POST';  // Create method
        }

        // Data to create or update the collection
        $collectionData = [
            'custom_collection' => [
                'title' => $pokemonSet['name'],  // Title of the collection
                'handle' => $handle,  // Handle of the collection
                'image' => [
                    'src' => $imageUrl,  // Image URL for the collection
                    'alt' => $pokemonSet['name'],  // Alt text for the image
                ],
            ]
        ];

        // Use cURL to send the request to Shopify API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "X-Shopify-Access-Token: $accessToken",  // Authentication using Access Token
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);  // POST or PUT depending on create or update
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($collectionData));  // Send data

        $response = curl_exec($ch);
        curl_close($ch);

        // Decode the response
        $responseData = json_decode($response, true);

        // Print response data for debugging
        // echo '<pre>';
        // print_r($responseData);
        // echo '</pre>';

        // Check if the collection creation or update was successful
        if (isset($responseData['custom_collection'])) {
            echo 'Shopify Collection Created or Updated successfully.<br>';
            echo 'Collection ID: ' . $responseData['custom_collection']['id'] . '<br>';
            echo 'Collection Title: ' . $responseData['custom_collection']['title'] . '<br>';
            echo 'Collection Handle: ' . $responseData['custom_collection']['handle'] . '<br>';
            echo 'Collection Image URL: ' . $responseData['custom_collection']['image']['src'] . '<br>';
            $collectionId = $responseData['custom_collection']['id'];

            // Step 1: Fetch existing metafields for the collection
            $existingMetafieldsResponse = $this->shopifyApiRequest('GET', "custom_collections/{$collectionId}/metafields.json");
            $existingMetafields = [];
            if (!empty($existingMetafieldsResponse['metafields'])) {
                foreach ($existingMetafieldsResponse['metafields'] as $meta) {
                    $existingMetafields[$meta['key']] = $meta;
                }
            }

            // Step 2: Define the metafields to assign
            $metafieldsToAssign = [
                [
                    "name" => "is_pokemon_set_collection",
                    "namespace" => "custom",
                    "key" => "is_pokemon_set_collection",
                    "value" => true,
                    "type" => "boolean",
                ],
                [
                    "name" => "set_era_group_name",
                    "namespace" => "custom",
                    "key" => "set_era_group_name",
                    "value" => $cardData['set']['series'],
                    "type" => "single_line_text_field",
                ]
            ];

            // Step 3: Loop through metafields to assign them
            foreach ($metafieldsToAssign as $metafield) {
                if (isset($existingMetafields[$metafield['key']])) {
                    // Metafield exists, update it
                    $metafieldId = $existingMetafields[$metafield['key']]['id'];
                    $updateUrl = "metafields/{$metafieldId}.json";
                    $this->shopifyApiRequest('PUT', $updateUrl, ['metafield' => array_merge(['id' => $metafieldId], $metafield)]);
                } else {
                    // Metafield does not exist, create it
                    $createUrl = "custom_collections/{$collectionId}/metafields.json";
                    $this->shopifyApiRequest('POST', $createUrl, ['metafield' => array_merge(['owner_resource' => 'custom_collection', 'owner_id' => $collectionId], $metafield)]);
                }
            }

            return $collectionId; // Return the Shopify collection ID
        } else {
            echo 'Failed to create or update Shopify collection.<br>';
            echo 'Response error: ' . $response . '<br>';
            return false;
        }
    }

    // public function CreateOrUpdateShopifyCollection($shopify, $handle, $pokemonSet) {
    //     $shopifyConfig = [
    //         'ShopUrl' => '18d497-2.myshopify.com', // Your Shopify store URL
    //         'AccessToken' => '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', // Your valid Access Token
    //     ];
    
    //     if (empty($pokemonSet['name'])) {
    //         echo "Error: Collection name is required.\n";
    //         return false;
    //     }
    
    //     if (empty($pokemonSet['images']['logo'])) {
    //         echo "Error: Collection image URL is missing.\n";
    //         return false;
    //     }
    
    //     echo "Collection name: " . $pokemonSet['name'] . "\n";
    //     echo "Image URL: " . $pokemonSet['images']['logo'] . "\n";
    
    //     $imageUrl = $pokemonSet['images']['logo'];
    //     if (!@getimagesize($imageUrl)) {
    //         echo "Invalid image URL: $imageUrl\n";
    //         return false;
    //     }
    
    //     // Shopify API configuration
    //     $shopUrl = "https://{$shopifyConfig['ShopUrl']}/admin/api/2024-10/custom_collections.json"; // Corrected access
    //     $accessToken = $shopifyConfig['AccessToken'];  // Corrected access
    
    //     // Use cURL to check existing collections
    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, $shopUrl);  // Correct endpoint for custom collections
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, [
    //         "Content-Type: application/json",
    //         "X-Shopify-Access-Token: $accessToken",  // Authentication using Access Token
    //     ]);
    
    //     $response = curl_exec($ch);
    //     curl_close($ch);
    
    //     // Decode the response
    //     $existingCollections = json_decode($response, true);
    
    //     // Check if there was an error decoding the response
    //     if (is_null($existingCollections)) {
    //         echo 'Error decoding JSON response.<br>';
    //         echo 'Response: ' . $response . '<br>';  // Print raw response for debugging
    //         return false;
    //     }
    
    //     // Search for existing collection by handle
    //     $existingCollection = null;
    //     foreach ($existingCollections['custom_collections'] as $collection) {
    //         if ($collection['handle'] === $handle) {
    //             $existingCollection = $collection;
    //             break;
    //         }
    //     }
    
    //     // If collection exists, update it, else create a new collection
    //     if ($existingCollection) {
    //         echo "Collection found. Updating...\n";
    //         $collectionId = $existingCollection['id'];  // Get the collection ID
    //         $url = "https://{$shopifyConfig['ShopUrl']}/admin/api/2024-10/custom_collections/{$collectionId}.json";  // URL for update
    //         $method = 'PUT';  // Update method
    //     } else {
    //         echo "Collection not found. Creating...\n";
    //         $url = "https://{$shopifyConfig['ShopUrl']}/admin/api/2024-10/custom_collections.json";  // URL for creation
    //         $method = 'POST';  // Create method
    //     }
    
    //     // Data to create or update the collection
    //     $collectionData = [
    //         'custom_collection' => [
    //             'title' => $pokemonSet['name'],  // Title of the collection
    //             'handle' => $handle,  // Handle of the collection
    //             'image' => [
    //                 'src' => $imageUrl,  // Image URL for the collection
    //                 'alt' => $pokemonSet['name'],  // Alt text for the image
    //             ],
    //         ]
    //     ];
    
    //     // Use cURL to send the request to Shopify API
    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, $url);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, [
    //         "Content-Type: application/json",
    //         "X-Shopify-Access-Token: $accessToken",  // Authentication using Access Token
    //     ]);
    //     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);  // POST or PUT depending on create or update
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($collectionData));  // Send data
    
    //     $response = curl_exec($ch);
    //     curl_close($ch);
    
    //     // Decode the response
    //     $responseData = json_decode($response, true);
    
    //     // Print response data for debugging
    //     echo '<pre>';
    //     print_r($responseData);
    //     echo '</pre>';
    
    //     // Check if the collection creation or update was successful
    //     if (isset($responseData['custom_collection'])) {
    //         echo 'Shopify Collection Created or Updated successfully.<br>';
    //         echo 'Collection ID: ' . $responseData['custom_collection']['id'] . '<br>';
    //         echo 'Collection Title: ' . $responseData['custom_collection']['title'] . '<br>';
    //         echo 'Collection Handle: ' . $responseData['custom_collection']['handle'] . '<br>';
    //         echo 'Collection Image URL: ' . $responseData['custom_collection']['image']['src'] . '<br>';
    //         return $responseData['custom_collection']['id'];
    //     } else {
    //         echo 'Failed to create or update Shopify collection.<br>';
    //         echo 'Response error: ' . $response . '<br>';
    //         return false;
    //     }
    // }

    private function shopifyApiRequest($method, $url, $data = []) {
        $shopifyConfig = [
            'ShopUrl' => '18d497-2.myshopify.com',
            'AccessToken' => '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',
        ];
    
        $ch = curl_init();
        $headers = [
            "Content-Type: application/json",
            "X-Shopify-Access-Token: {$shopifyConfig['AccessToken']}",
        ];
    
        curl_setopt($ch, CURLOPT_URL, "https://{$shopifyConfig['ShopUrl']}/admin/api/2024-10/{$url}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
        if ($method === 'POST' || $method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
    
        return json_decode($response, true);
    }

    public static function convertDescription($cardData) {
        // echo "<pre>";
        // print_r($cardData);
        // echo "</pre>";

        $types = isset($cardData['types']) ? self::convertArraytoCommaSeperatedHtml($cardData['types'], 'Types') : 'No types available';
        $evolvesTO = isset($cardData['evolves_to']) ? self::convertArraytoCommaSeperatedHtml($cardData['evolves_to'], 'Evolves To') : 'No evolves to information';
        $level = isset($cardData['level']) ? $cardData['level'] : 'Basic';
        $evolvesFrom = isset($cardData['evolves_from']) ? '<li><strong>Evolves From:</strong>' . htmlspecialchars($cardData['evolves_from'], ENT_QUOTES, 'UTF-8') . '</li>' : '';
        $rules = isset($cardData['rules']) ? self::convertArraytolistHtml($cardData['rules'], 'Rules') : 'No rules available';
        $attacks = isset($cardData['attacks']) ? self::convertArraytotableFormatHtml($cardData['attacks'], 'Attacks') : 'No attacks available';
        $weaknesses = isset($cardData['weaknesses']) ? $cardData['weaknesses'] : 'No weaknesses available';
        $resistances = isset($cardData['resistances']) ? $cardData['resistances'] : 'No resistances available';
        $setName = isset($cardData['set']['name']) ?  htmlspecialchars($cardData['set']['name'], ENT_QUOTES, 'UTF-8') : '-';
        $number = isset($cardData['number']) ?  htmlspecialchars($cardData['number'], ENT_QUOTES, 'UTF-8') : '-';
        $totalPrinted = isset($cardData['set']['printedTotal']) ?  htmlspecialchars($cardData['set']['printedTotal'], ENT_QUOTES, 'UTF-8') : '-';
        $artist = isset($cardData['artist']) ?  htmlspecialchars($cardData['artist'], ENT_QUOTES, 'UTF-8') : '-';
        $rarity = isset($cardData['rarity']) ?  htmlspecialchars($cardData['rarity'], ENT_QUOTES, 'UTF-8') : '-';

        $subtypes = isset($cardData['subtypes']) && is_array($cardData['subtypes']) 
            ? implode(', ', $cardData['subtypes']) 
            : 'No subtypes available';

        $retreatCost = isset($cardData['retreat_cost']) && is_array($cardData['retreat_cost']) 
            ? implode(', ', $cardData['retreat_cost']) 
            : 'No reatreat cost available';

        if (is_string($weaknesses)) {
            $weaknessesArray = json_decode($weaknesses, true);
        } else if (is_array($weaknesses)) {
            $weaknessesArray = $weaknesses;
        } else {
            $weaknessesArray = [];
        }

        if (is_array($weaknessesArray)) {
            $newFormatWeakness = '';
            foreach ($weaknessesArray as $weakness) {
                $type = isset($weakness['type']) ? $weakness['type'] : 'Unknown';
                $value = isset($weakness['value']) ? $weakness['value'] : 'Unknown';

                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                $newFormatWeakness .= $type . ', ' . $value . "<br>";
            }
        } else {
            echo 'Invalid weaknesses data.';
        }

        if (is_array($resistances)) {
            $formattedResistances = '';
            foreach ($resistances as $resistance) {
                $type = isset($resistance['type']) ? $resistance['type'] : 'Unknown';
                $value = isset($resistance['value']) ? $resistance['value'] : 'Unknown';
        
                $formattedResistances .= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "<br>";
            }
        } else {
            $formattedResistances = htmlspecialchars($resistances, ENT_QUOTES, 'UTF-8');
        }

        $html =  '
            <div style="width: 100%; max-width: 800px;">
            <div style="margin-bottom: 20px;">
                <div style="font-weight: 900; font-size: 18px; color: #e76a3c;"> ' . htmlspecialchars($cardData['name'], ENT_QUOTES, 'UTF-8') . '' . ($cardData['rarity'] ? ' - ' . $rarity : '') . '</div>
                <div style="font-weight: 200; font-size: 16px; "> ' . htmlspecialchars($cardData['supertype'], ENT_QUOTES, 'UTF-8') . ' - ' . $subtypes . '</div>
            </div>

            <div style="display: flex; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 150px; padding: 10px;  border: 2px solid #72b6e7; border-bottom: none; ">
                    <strong style="color: #e76a3c;">LEVEL</strong> <br> ' . $level   . '
                </div>
                <div style="flex: 1; min-width: 150px; padding: 10px;  border: 2px solid #72b6e7;  border-bottom: none; border-left: none; border-right: none;">
                    <strong style="color: #e76a3c;">HP</strong> <br> ' . (isset($cardData['hp']) ? htmlspecialchars($cardData['hp'], ENT_QUOTES, 'UTF-8') : 'N/A')   . '
                </div>
                <div style="flex: 1; min-width: 150px; padding: 10px; border: 2px solid #72b6e7;  border-bottom: none; ">
                    <strong style="color: #e76a3c;">WEAKNESS</strong> <br> ' . $newFormatWeakness   . '
                </div>
            </div>

            <div style="display: flex; flex-wrap: wrap;  ">
                <div style="flex: 1; min-width: 150px; padding: 10px; border: 2px solid #72b6e7; border-right: none; border-bottom: none;">
                    <strong style="color: #e76a3c;">RESISTANCE</strong> <br> ' . $formattedResistances   . '
                </div>
                <div style="flex: 1; min-width: 150px; padding: 10px; border: 2px solid #72b6e7; border-right: none;  border-bottom: none;">
                    <strong style="color: #e76a3c;">RETREAT COST</strong> <br> ' . $retreatCost   . '
                </div>
                <div style="flex: 1; min-width: 150px; padding: 10px; border: 2px solid #72b6e7; border-bottom: none;">
                    <strong style="color: #e76a3c;">ARTIST</strong> <br> ' . $artist   . '
                </div>
            </div>

            <div style="display: flex; flex-wrap: wrap;  ">
                <div style="flex: 1; min-width: 150px; padding: 10px;  border: 2px solid #72b6e7; border-right: none;">
                    <strong style="color: #e76a3c;">RARITY</strong> <br> ' . $rarity   . '
                </div>
                <div style="flex: 1; min-width: 150px; padding: 10px; border: 2px solid #72b6e7; border-right: none;">
                    <strong style="color: #e76a3c;">SET</strong> <br> ' . $setName   . '
                </div>
                <div style="flex: 1; min-width: 150px; padding: 10px; border: 2px solid #72b6e7;">
                    <strong style="color: #e76a3c;">NUMBER</strong> <br> ' .  $number  . ' / ' . $totalPrinted    . '
                </div>
            </div>
            ' . $attacks . '
            <p style="font-style:italic;">' . (isset($cardData['flavor_text']) ? htmlspecialchars($cardData['flavor_text'], ENT_QUOTES, 'UTF-8') : 'No flavor text available') . '</p>';

        return $html;
    }

    public static function convertArraytotableFormatHtml($dataArray, $title)
    {
        if (empty($dataArray)) {
            return '';
        }

        if (is_string($dataArray)) {
            $dataArray = json_decode($dataArray, true);
        }

        if (empty($dataArray)) {
            return '';
        }

        $html = '<div style="margin-top: 10px;">
                    <p><strong style="color: #e76a3c;">ATTACKS</strong></p>';

        foreach ($dataArray as $data) {
            $damage = !empty($data['damage']) ? ' (Damage: ' . $data['damage'] . ')' : '';
            $html .= '<p style="margin-top: 0px;margin-bottom: 0px;color: #f88d67;">' . $data['name'] . $damage . '  </p>';
        }

        $html .= '</div>';
        return $html;
    }

    public static function convertArraytolistHtml($dataArray, $title)
    {
        if (empty($dataArray)) {
            return '';
        }
        $html = '<ul>';
        foreach ($dataArray as $data) {
            $html .= '<li>' . $data . '</li>';
        }
        $html .= '</ul>';

        $html = '<li><strong>' . $title . ':</strong> ' . $html . '</li>';
        return $html;
    }

    public static function convertArraytoCommaSeperatedHtml($dataArray, $title) {
        if (empty($dataArray)) {
            return '';
        }
    
        if (is_string($dataArray)) {
            $decodedArray = json_decode($dataArray, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return '';
            }
            $dataArray = $decodedArray;
        }
        if (!is_array($dataArray)) {
            return ''; 
        }
        $html = implode(', ', $dataArray);
        $html = '<li><strong>' . $title . ':</strong> ' . $html . '</li>';
        
        return $html;
    }
    
    public function getPrices($cards) {
        $price = 0;
    
        if (isset($cards['tcgplayer'])) {
            // Diretso na gamitin ang array ng `tcgplayer`
            $tcgplayerData = $cards['tcgplayer'];
    
            if (isset($tcgplayerData['prices']['holofoil']['market'])) {
                $price = $tcgplayerData['prices']['holofoil']['market'];
            } elseif (isset($tcgplayerData['prices']['normal']['market'])) {
                $price = $tcgplayerData['prices']['normal']['market'];
            } elseif (isset($tcgplayerData['prices']['reverseHolofoil']['market'])) {
                $price = $tcgplayerData['prices']['reverseHolofoil']['market'];
            } elseif (isset($tcgplayerData['prices']['unlimited']['market'])) {
                $price = $tcgplayerData['prices']['unlimited']['market'];
            } elseif (isset($tcgplayerData['prices']['1stEdition']['market'])) {
                $price = $tcgplayerData['prices']['1stEdition']['market'];
            }
        }
    
        echo $price;
    
        if ($price == 0) {
            echo "No valid market price found";
        }
    
        if ($price == 0 || $price == null || $price < 0) {
            $price = $this->getCardMinimumPriceAsPerClientRequest($cards);
        }
    
        return [
            "normal" => [
                "market" => $price
            ]
        ];
    }    

    // public function getPrices($cards) {
    //     $price = 0;

    //     if (isset($cards['tcgplayer'])) {
    //         $tcgplayerData = json_decode($cards['tcgplayer'], true);

    //         if (isset($tcgplayerData['prices']['holofoil']['market'])) {
    //             $price = $tcgplayerData['prices']['holofoil']['market'];
    //         } elseif (isset($tcgplayerData['prices']['normal']['market'])) {
    //             $price = $tcgplayerData['prices']['normal']['market'];
    //         } elseif (isset($tcgplayerData['prices']['reverseHolofoil']['market'])) {
    //             $price = $tcgplayerData['prices']['reverseHolofoil']['market'];
    //         } elseif (isset($tcgplayerData['prices']['unlimited']['market'])) {
    //             $price = $tcgplayerData['prices']['unlimited']['market'];
    //         } elseif (isset($tcgplayerData['prices']['1stEdition']['market'])) {
    //             $price = $tcgplayerData['prices']['1stEdition']['market'];
    //         }
    //     }

    //     echo $price;

    //     if ($price == 0) {
    //         echo "No valid market price found";
    //     }

    //     if ($price == 0 || $price == null || $price < 0) {
    //         $price = $this->getCardMinimumPriceAsPerClientRequest($cards);
    //     }

    //     return [
    //         "normal" => [
    //             "market" => $price
    //         ]
    //     ];
    // }

    public function getCardMinimumPriceAsPerClientRequest($cards, $editionNames = null) {
        $minPriceConfig = config('tcg-min-price');
        $defaultPrice = null;
    
        if (!is_array($minPriceConfig)) {
            echo "Min price configuration is missing or invalid.\n";
            return $defaultPrice;
        }

        if (isset($cards['set'])) {
            $set = is_array($cards['set']) ? $cards['set'] : json_decode($cards['set'], true);
        } else {
            echo "Card set is missing.\n";
            return $defaultPrice;
        }
    
        if (isset($set['name'])) {
            $setName = $set['name'];
        } else {
            echo "Card set name is missing.\n";
            return $defaultPrice;
        }
    
        if (isset($set['series'])) {
            $setSeries = $set['series'];
        } else {
            echo "Card set series is missing.\n";
            return $defaultPrice;
        }
    
        foreach ($minPriceConfig as $priceConfig) {
            if ($priceConfig['type'] === 'set' && isset($priceConfig['data'][$setName])) {
                $setPriceData = $priceConfig['data'][$setName];
                if (isset($setPriceData[$cards['rarity']])) {
                    return isset($setPriceData[$cards['rarity']]) ? $setPriceData[$cards['rarity']] : $defaultPrice;
                }
            } else if ($priceConfig['type'] === 'Edition' && isset($priceConfig['data'][$setName])) {
                if (isset($priceConfig['data'][$setName][$editionNames])) {
                    $setPriceData = $priceConfig['data'][$setName][$editionNames];
                    return isset($setPriceData[$cards['rarity']]) ? $setPriceData[$cards['rarity']] : $defaultPrice;
                }
            } else if ($priceConfig['type'] === 'EditionWithSetName' && isset($priceConfig['data'][$setName])) {
                if (isset($priceConfig['data'][$setName][$editionNames])) {
                    $setPriceData = $priceConfig['data'][$setName][$editionNames];
                    return isset($setPriceData[$cards['rarity']]) ? $setPriceData[$cards['rarity']] : $defaultPrice;
                }
            } else if ($priceConfig['type'] === 'series' && isset($priceConfig['data'][$setSeries])) {
                $seriesPriceData = $priceConfig['data'][$setSeries];
                if (isset($seriesPriceData[$cards['rarity']])) {
                    return isset($setPriceData[$cards['rarity']]) ? $setPriceData[$cards['rarity']] : $defaultPrice;
                }
            }
        }
    
        return $defaultPrice;
    }

    public static function currencyConverter($price) {
        logToFile('Currency Converter Called');
        
        $cacheFile = __DIR__ . '/cache/USD_to_AUD_Rate.cache';
        $cacheLifetime = 1320;  // Cache lifetime in seconds
        $currentRate = null;
    
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheLifetime) {
            $currentRate = file_get_contents($cacheFile);
        } else {
            $currencyApiKey = config('shopify.currency_converter_api_key');
            $baseCurrency = config('shopify.currency_converter_base_currency');
            $currencies = config('shopify.currency_converter_currencies');
            
            $currencyApi = new ExchangeRatesAPI($currencyApiKey);
            
            try {
                $rates = $currencyApi->fetch();
    
                if (isset($rates->source) && isset($rates->quotes)) {
                    $currentRate = $rates->quotes->{'USD' . $currencies};
                } else {
                    logToFile('Error: Missing rate in API response');
                    $currentRate = 1;  // Default na halaga kung walang rates
                }
    
                // Make sure cache directory exists
                if (!file_exists(__DIR__ . '/cache')) {
                    mkdir(__DIR__ . '/cache', 0777, true);  // Create cache directory kung wala
                }
                
                file_put_contents($cacheFile, $currentRate);
            } catch (\Exception $e) {
                logToFile('Error fetching exchange rate: ' . $e->getMessage());
                $currentRate = 1;  // Default na halaga kung may error sa API
            }
        }
    
        logToFile('Current Rate: ' . $currentRate);
    
        $actualPrice = $price ?? 1;  // Default to 1 if no price provided
        logToFile('Actual Price: ' . $actualPrice);
    
        $tax = config('shopify.product_tax_in_percentage');
        $afterConvertedPrice = ($actualPrice * $currentRate) + ($actualPrice * $currentRate * $tax / 100);
    
        logToFile('Converted Price: ' . $afterConvertedPrice);
    
        return $afterConvertedPrice;
    }    

    public static function priceConvertForVariant($price, $percantage) {
        $price = $price - ($price * $percantage / 100);
        return $price;
    }
}

$shopifyConfig = [
    'ShopUrl' => '18d497-2.myshopify.com',
    'AccessToken' => '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',
];

try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3308;dbname=shopify_dev', 'root', 'password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error connecting to the database: " . $e->getMessage());
}

$shopifyAPI = new TcgPokemonAPI($shopifyConfig, $pdo);
$shopifyAPI->GetCardsAPI();
?>