<?php
set_time_limit(300); // Para hindi mag-timeout kung malaki ang data
require_once('../vendor/autoload.php');

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

class ShopifyAPI {
    private $shopify;
    private $db;

    public function __construct($config, $db) {
        $this->shopifyConfig = $config;
        PHPShopify\ShopifySDK::config($config);
        $this->shopify = new PHPShopify\ShopifySDK();
        $this->db = $db;
    }

    // Get Data mula sa Pokémon TCG API
    public function GetCardsAPI() {
        $url = "https://api.pokemontcg.io/v2/cards/base4-4";

        $response = file_get_contents($url);

        if ($response === FALSE) {
            echo "Error fetching data from API.";
            return;
        }

        $data = json_decode($response, true);

        if (isset($data['data'])) {
            $this->postProductCardDBAndShopify($data['data']);
        } else {
            echo "No data found.";
        }
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

        $priceFormat = $cardData['tcgplayer'];

        if (isset($priceFormat['prices'])) {
            $prices = $priceFormat['prices'];
        } else {
            // tawagin ang getPrices na function
            $prices = $this->getPrices($cardData);
        }

        $editionNames = config('tcg-edition-names');
    }

    public function getPrices($cards) {
        $price = 0;

        if (isset($cards['tcgplayer'])) {
            $tcgplayerData = json_decode($cards['tcgplayer'], true);

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

    public function getCardMinimumPriceAsPerClientRequest($cards, $editionNames = null) {
        var_dump($cards);
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

$shopifyAPI = new ShopifyAPI($shopifyConfig, $pdo);
$shopifyAPI->GetCardsAPI();
?>