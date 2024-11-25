<?php

header('Content-Type: text/html; charset=UTF-8');

class CommonHelper
{
    // Constructor para sa pag-initialize ng object
    public function __construct()
    {
        // Pwede mong ilagay dito kung may kailangan pang initialization
    }

    // Pag-convert ng currency
    public function currencyConverter($price)
    {
        logger('Currency Converter Called');
        $currentRate = Cache::remember('USD_to_AUD_Rate', '1320', function () {
            $currencyApi = new \CurrencyApi\CurrencyApi\CurrencyApiClient(config('shopify.currency_converter_api_key'));

            $data = $currencyApi->latest([
                'base_currency' => config('shopify.currency_converter_base_currency'),
                'currencies' => config('shopify.currency_converter_currencies'),
            ]);

            return $data['data']['AUD']['value'] ?? 1;
        });

        $actualPrice = $price ?? 1; // TCG Card Price
        logger($actualPrice);

        $tax = config('shopify.product_tax_in_percentage'); // TAX included in the product

        $afterConvertedPrice = ($actualPrice * $currentRate) + ($actualPrice * $currentRate * $tax / 100); // Converted Price with TAX and USD to AUD rate
        logger($afterConvertedPrice);

        return $afterConvertedPrice;
    }

    // Pag-convert ng presyo ng variant
    public function priceConvertForVariant($price, $percantage)
    {
        $price = $price - ($price * $percantage / 100);
        return $price;
    }

    public function convertDescription($cards)
    {
        header('Content-Type: text/html; charset=UTF-8');

        // Safe check with isset or empty for array keys
        $types = isset($cards['types']) ? $this->convertArraytoCommaSeperatedHtml($cards['types'], 'Types') : 'No types available';
        $evolvesTO = isset($cards['evolves_to']) ? $this->convertArraytoCommaSeperatedHtml($cards['evolves_to'], 'Evolves To') : 'No evolves to information';
        $level = isset($cards['level']) ? $cards['level'] : 'Basic';  // Default 'Basic' if level is not provided
        $evolvesFrom = isset($cards['evolves_from']) ? '<li><strong>Evolves From:</strong>' . htmlspecialchars($cards['evolves_from'], ENT_QUOTES, 'UTF-8') . '</li>' : '';
        $rules = isset($cards['rules']) ? $this->convertArraytolistHtml($cards['rules'], 'Rules') : 'No rules available';
        $attacks = isset($cards['attacks']) ? $this->convertArraytotableFormatHtml($cards['attacks'], 'Attacks') : 'No attacks available';
        
        // Fallback for weaknesses and resistances if not set or invalid
        $weaknesses = isset($cards['weaknesses']) ? $cards['weaknesses'] : 'No weaknesses available';
        $resistances = isset($cards['resistances']) ? $cards['resistances'] : 'No resistances available';

        $setName = isset($cards['set']['name']) ?  htmlspecialchars($cards['set']['name'], ENT_QUOTES, 'UTF-8') : '-';
        $number = isset($cards['number']) ?  htmlspecialchars($cards['number'], ENT_QUOTES, 'UTF-8') : '-';
        $totalPrinted = isset($cards['set']['printedTotal']) ?  htmlspecialchars($cards['set']['printedTotal'], ENT_QUOTES, 'UTF-8') : '-';
        $artist = isset($cards['artist']) ?  htmlspecialchars($cards['artist'], ENT_QUOTES, 'UTF-8') : '-';
        $rarity = isset($cards['rarity']) ?  htmlspecialchars($cards['rarity'], ENT_QUOTES, 'UTF-8') : '-';

        // Handling subtypes, ensure proper joining and escaping
        $subtypes = isset($cards['subtypes']) ? 
            (is_array($cards['subtypes']) ? implode(', ', $cards['subtypes']) : 
            (is_string($cards['subtypes']) ? implode(', ', json_decode($cards['subtypes'], true)) : '')) 
            : '';

        // Process retreat_cost (array or string) and ensure safe output
        $retreatCost = isset($cards['retreat_cost']) ? 
            (is_array($cards['retreat_cost']) ? implode(', ', $cards['retreat_cost']) : 
            (is_string($cards['retreat_cost']) ? implode(', ', json_decode($cards['retreat_cost'], true)) : '')) 
            : '';

        // Decode weaknesses and resistances (ensure valid JSON)
        $weaknessesArray = json_decode($weaknesses, true);
        $resistancesArray = json_decode($resistances, true);

        $weaknesses = isset($cards['weaknesses']) ? $cards['weaknesses'] : 'No weaknesses available';
        // Convert from ASCII to UTF-8
        $weaknesses = mb_convert_encoding($weaknesses, 'UTF-8', 'ASCII');
        $weaknesses = str_replace('?2', '×2', $weaknesses);
        $weaknessesArray = json_decode($weaknesses, true);

        if (is_array($weaknessesArray)) {
            foreach ($weaknessesArray as $weakness) {
                $type = isset($weakness['type']) ? $weakness['type'] : null;
                $value = isset($weakness['value']) ? $weakness['value'] : null;
        
                // Escape the value for safe HTML output
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                
                // Display the type and value
                $newFormatWeakness = $type . ', ' . $value . "<br>";
            }
        } else {
            echo 'Invalid JSON format';  // If json_decode fails
        }

        // Format weaknesses with checks
        // $newFormatWeakness = '';
        // if (is_array($weaknessesArray)) {
        //     foreach ($weaknessesArray as $weakness) {
        //         $type = isset($weakness['type']) ? htmlspecialchars($weakness['type'], ENT_QUOTES, 'UTF-8') : null;
        //         $value = isset($weakness['value']) ? htmlspecialchars($weakness['value'], ENT_QUOTES, 'UTF-8') : null;
        //         if ($type && $value) {
        //             $newFormatWeakness .= $type . ', ' . $value . "<br>";
        //         }
        //     }
        // }

        // Format resistances with checks
        $resistanceFormat = '';
        if (is_array($resistancesArray)) {
            foreach ($resistancesArray as $resistance) {
                $type = isset($resistance['type']) ? htmlspecialchars($resistance['type'], ENT_QUOTES, 'UTF-8') : null;
                $value = isset($resistance['value']) ? htmlspecialchars($resistance['value'], ENT_QUOTES, 'UTF-8') : null;
                if ($type && $value) {
                    $resistanceFormat .= $type . ', ' . $value . "<br>";
                }
            }
        }

        // Generate the HTML output
        $html =  '
            <div style="width: 100%; max-width: 800px;">
            <div style="margin-bottom: 20px;">
                <div style="font-weight: 900; font-size: 18px; color: #e76a3c;"> ' . htmlspecialchars($cards['name'], ENT_QUOTES, 'UTF-8') . '' . ($cards['rarity'] ? ' - ' . $rarity : '') . '</div>
                <div style="font-weight: 200; font-size: 16px; "> ' . htmlspecialchars($cards['supertype'], ENT_QUOTES, 'UTF-8') . ' - ' . $subtypes . '</div>
            </div>

            <div style="display: flex; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 150px; padding: 10px;  border: 2px solid #72b6e7; border-bottom: none; ">
                    <strong style="color: #e76a3c;">LEVEL</strong> <br> ' . $level   . '
                </div>
                <div style="flex: 1; min-width: 150px; padding: 10px;  border: 2px solid #72b6e7;  border-bottom: none; border-left: none; border-right: none;">
                    <strong style="color: #e76a3c;">HP</strong> <br> ' . (isset($cards['hp']) ? htmlspecialchars($cards['hp'], ENT_QUOTES, 'UTF-8') : 'N/A')   . '
                </div>
                <div style="flex: 1; min-width: 150px; padding: 10px; border: 2px solid #72b6e7;  border-bottom: none; ">
                    <strong style="color: #e76a3c;">WEAKNESS</strong> <br> ' . $newFormatWeakness   . '
                </div>
            </div>

            <div style="display: flex; flex-wrap: wrap;  ">
                <div style="flex: 1; min-width: 150px; padding: 10px; border: 2px solid #72b6e7; border-right: none; border-bottom: none;">
                    <strong style="color: #e76a3c;">RESISTANCE</strong> <br> ' . $resistanceFormat   . '
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
            <p style="font-style:italic;">' . (isset($cards['flavor_text']) ? htmlspecialchars($cards['flavor_text'], ENT_QUOTES, 'UTF-8') : 'No flavor text available') . '</p>';

        return $html;
    }


    // public function convertDescription($card)
    // {
    //     header('Content-Type: text/html; charset=UTF-8');
    //     // Safe check with isset or empty for array keys
    //     $types = isset($card['types']) ? $this->convertArraytoCommaSeperatedHtml($card['types'], 'Types') : 'No types available';
    //     $evolvesTO = isset($card['evolves_to']) ? $this->convertArraytoCommaSeperatedHtml($card['evolves_to'], 'Evolves To') : 'No evolves to information';
    //     $level = isset($card['level']) ? $card['level'] : 'Basic';  // Default 'Basic' if level is not provided
    //     $evolvesFrom = isset($card['evolves_from']) ? '<li><strong>Evolves From:</strong>' . $card['evolves_from'] . '</li>' : '';
    //     $rules = isset($card['rules']) ? $this->convertArraytolistHtml($card['rules'], 'Rules') : 'No rules available';
    //     $attacks = isset($card['attacks']) ? $this->convertArraytotableFormatHtml($card['attacks'], 'Attacks') : 'No attacks available';
    //     $weaknesses = isset($card['weaknesses']) ? $this->convertArraytolistWithKeyPairHtml($card['weaknesses'], 'Weaknesses') : 'No weaknesses available';
    //     $resistances = isset($card['resistances']) ? $this->convertArraytolistWithKeyPairHtml($card['resistances'], 'Resistances') : 'No resistances available';
    //     $setName = isset($card['set']['name']) ?  $card['set']['name'] : '-';
    //     print_r($setName);
    //     $number = isset($card['number']) ?  $card['number']  : '-';
    //     $totalPrinted = isset($card['set']['printedTotal']) ?  $card['set']['printedTotal'] : '-';
    //     $artist = isset($card['artist']) ?  $card['artist'] : '-';
    //     $rarity = isset($card['rarity']) ?  $card['rarity'] : '-';

    //     // need to get all subtypes and convert to comma separated string
    //     $subtypes = isset($card['subtypes']) ? 
    //         (is_array($card['subtypes']) ? implode(', ', $card['subtypes']) : 
    //         (is_string($card['subtypes']) ? implode(', ', json_decode($card['subtypes'], true)) : '')) 
    //         : '';
    //     // $subtypes = isset($card['subtypes']) ? implode(', ', $card['subtypes']) : '';

    //     $retreatCost = isset($card['retreat_cost']) ? 
    //         (is_array($card['retreat_cost']) ? implode(', ', $card['retreat_cost']) : 
    //         (is_string($card['retreat_cost']) ? implode(', ', json_decode($card['retreat_cost'], true)) : '')) 
    //         : '';

    //     $setName = isset($card['set']) ? $card['set'] : 'No Base Name';
    //     $setNameArray = json_decode($setName, true);

    //     if (isset($setNameArray['name'])) {
    //         $setName = $setNameArray['name'];
    //     } else {
    //         $setName = 'No Name';  // Default if 'name' is not found
    //     }
        
    //     $resistances = isset($card['resistances']) ? $card['resistances'] : 'No resistances available';

    //     // Decode the JSON string into a PHP array
    //     $resistancesArray = json_decode($resistances, true);
            
    //     // Check if decoding was successful
    //     if (is_array($resistancesArray)) {
    //         foreach ($resistancesArray as $resistance) {
    //             $type = isset($resistance['type']) ? $resistance['type'] : null;
    //             $value = isset($resistance['value']) ? $resistance['value'] : null;
                    
    //             // Escape the value for safe HTML output
    //             $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    
    //             // Display the type and value (including the negative value for Fighting type)
    //             $resistanceFormat = $type . ', ' . $value . "<br>";
    //         }
    //     } else {
    //         echo 'Invalid JSON format';  // If json_decode fails
    //     }
            

    //     $weaknesses = isset($card['weaknesses']) ? $card['weaknesses'] : 'No weaknesses available';
    //     // Convert from ASCII to UTF-8
    //     $weaknesses = mb_convert_encoding($weaknesses, 'UTF-8', 'ASCII');
    //     $weaknesses = str_replace('?2', '×2', $weaknesses);
    //     $weaknessesArray = json_decode($weaknesses, true);

    //     if (is_array($weaknessesArray)) {
    //         foreach ($weaknessesArray as $weakness) {
    //             $type = isset($weakness['type']) ? $weakness['type'] : null;
    //             $value = isset($weakness['value']) ? $weakness['value'] : null;
        
    //             // Escape the value for safe HTML output
    //             $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                
    //             // Display the type and value
    //             $newFormatWeakness = $type . ', ' . $value . "<br>";
    //         }
    //     } else {
    //         echo 'Invalid JSON format';  // If json_decode fails
    //     }

    //     // HTML output
    //     $html =  '
    //         <div style="width: 100%; max-width: 800px;">
    //         <div style="margin-bottom: 20px;">
    //             <div style="font-weight: 900; font-size: 18px; color: #e76a3c;"> ' . $card['name'] . '' . ($card['rarity'] ? ' - ' . $card['rarity'] : '') . '</div>
    //             <div style="font-weight: 200; font-size: 16px; "> ' . $card['supertype'] . ' - ' . $subtypes . '</div>
    //         </div>

    //         <div style="display: flex; flex-wrap: wrap;">
    //             <div style="flex: 1; min-width: 150px; padding: 10px;  border: 2px solid #72b6e7; border-bottom: none; ">
    //                 <strong style="color: #e76a3c;">LEVEL</strong> <br> ' . $level   . '
    //             </div>
    //             <div style="flex: 1; min-width: 150px; padding: 10px;  border: 2px solid #72b6e7;  border-bottom: none; border-left: none; border-right: none;">
    //                 <strong style="color: #e76a3c;">HP</strong> <br> ' . (isset($card['hp']) ? $card['hp'] : 'N/A')   . '
    //             </div>
    //             <div style="flex: 1; min-width: 150px; padding: 10px; border: 2px solid #72b6e7;  border-bottom: none; ">
    //                 <strong style="color: #e76a3c;">WEAKNESS</strong> <br> ' . $newFormatWeakness   . '
    //             </div>
    //         </div>

    //         <div style="display: flex; flex-wrap: wrap;  ">
    //             <div style="flex: 1; min-width: 150px; padding: 10px; border: 2px solid #72b6e7; border-right: none; border-bottom: none;">
    //                 <strong style="color: #e76a3c;">RESISTANCE</strong> <br> ' . $resistanceFormat   . '
    //             </div>
    //             <div style="flex: 1; min-width: 150px; padding: 10px; border: 2px solid #72b6e7; border-right: none;  border-bottom: none;">
    //                 <strong style="color: #e76a3c;">RETREAT COST</strong> <br> ' . $retreatCost   . '
    //             </div>
    //             <div style="flex: 1; min-width: 150px; padding: 10px; border: 2px solid #72b6e7; border-bottom: none;">
    //                 <strong style="color: #e76a3c;">ARTIST</strong> <br> ' . $artist   . '
    //             </div>
    //         </div>

    //         <div style="display: flex; flex-wrap: wrap;  ">
    //             <div style="flex: 1; min-width: 150px; padding: 10px;  border: 2px solid #72b6e7; border-right: none;">
    //                 <strong style="color: #e76a3c;">RARITY</strong> <br> ' . $rarity   . '
    //             </div>
    //             <div style="flex: 1; min-width: 150px; padding: 10px; border: 2px solid #72b6e7; border-right: none;">
    //                 <strong style="color: #e76a3c;">SET</strong> <br> ' . $setName   . '
    //             </div>
    //             <div style="flex: 1; min-width: 150px; padding: 10px; border: 2px solid #72b6e7;">
    //                 <strong style="color: #e76a3c;">NUMBER</strong> <br> ' .  $number  . ' / ' . $totalPrinted    . '
    //             </div>
    //         </div>

    //     ' . $attacks . '
    //     <p style="font-style:italic;">' . (isset($card['flavor_text']) ? $card['flavor_text'] : 'No flavor text available') . '</p>';

    //     return $html;
    // }

    public function convertArraytoCommaSeperatedHtml($dataArray, $title)
    {
        // If the input is empty, return an empty string
        if (empty($dataArray)) {
            return '';
        }

        // If the input is a string, try to decode it into an array (JSON format expected)
        if (is_string($dataArray)) {
            $decodedArray = json_decode($dataArray, true);  // Decode to an associative array
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Return empty string if JSON is invalid
                return '';
            }
            $dataArray = $decodedArray; // Use the decoded array
        }

        // Ensure the data is now an array
        if (!is_array($dataArray)) {
            return '';  // Return empty string if it's still not an array
        }

        // Join the array into a comma-separated string
        $html = implode(', ', $dataArray);

        // Format the output as an HTML list item
        $html = '<li><strong>' . $title . ':</strong> ' . $html . '</li>';
        
        return $html;
    }


    // Helper function para sa pag-convert ng array to comma-separated list
    // public function convertArraytoCommaSeperatedHtml($dataArray, $title)
    // {
    //     print_r($dataArray);
    //     if (empty($dataArray)) {
    //         return '';
    //     }
    //     if (is_string($dataArray)) {
    //         $dataArray = json_decode($dataArray, true);  // Convert the string to an array
    //     }
    
    //     if (!is_array($dataArray)) {
    //         return '';  // Return empty string if it's still not an array
    //     }

    //     $html = '';
    //     $html = implode(', ', $dataArray);
    //     $html = '<li><strong>' . $title . ':</strong> ' . $html . '</li>';
    //     return $html;
    // }

    // // Convert array to list format (HTML)
    // public function convertArraytolistHtml($dataArray, $title)
    // {
    //     if (empty($dataArray)) {
    //         return '';
    //     }
    //     $html = '<ul>';
    //     foreach ($dataArray as $data) {
    //         $html .= '<li>' . $data . '</li>';
    //     }
    //     $html .= '</ul>';
    //     $html = '<li><strong>' . $title . ':</strong> ' . $html . '</li>';
    //     return $html;
    // }

    // Pag-convert ng array na may key-value pair sa HTML
    // public function convertArraytolistWithKeyPairHtml($dataArray, $title)
    // {
    //     if (empty($dataArray)) {
    //         return '';
    //     }
    //     $html = '';
    //     foreach ($dataArray as $key => $data) {
    //         $html .= '<li>' . $data['type'] . ': ' . $data['value'] . '</li>';
    //     }
    //     return $html;
    // }

    public function convertArraytolistWithKeyPairHtml($dataArray, $title)
    {
        // Check if the array is empty or not an array
        if (empty($dataArray) || !is_array($dataArray)) {
            return '';  // Return an empty string if it's not an array or is empty
        }

        $html = '';

        // Loop through the array and handle both simple and associative arrays
        foreach ($dataArray as $data) {
            // Check if each element is an associative array with 'type' and 'value'
            if (is_array($data) && isset($data['type']) && isset($data['value'])) {
                $html .= '<li>' . htmlspecialchars($data['type']) . ': ' . htmlspecialchars($data['value']) . '</li>';
            } else {
                // Handle the case where $data is not an associative array with 'type' and 'value'
                // For example, if $data is just a string or numeric value
                $html .= '<li>' . htmlspecialchars($data) . '</li>';
            }
        }

        return $html;
    }


    // Convert ng array sa table format (HTML)
    // public function convertArraytotableFormatHtml($dataArray, $title)
    // {
    //     if (empty($dataArray)) {
    //         return '';
    //     }

    //     $html = '<div style="margin-top: 10px;">
    //                 <p><strong style="color: #e76a3c;">ATTACKS</strong></p>
    //     ';
    //     foreach ($dataArray as $data) {
    //         $damage = !empty($data['damage']) ? ' (Damage: ' . $data['damage'] . ')' : '';
    //         $html .= '<p style="margin-top: 0px;margin-bottom: 0px;color: #f88d67;">' . $data['name'] . $damage . '  </p>
    //         <p>';
    //     }
    //     return $html;
    // }

    public function convertArraytotableFormatHtml($dataArray, $title)
    {
        // Kung empty ang array, magbabalik tayo ng empty string
        if (empty($dataArray)) {
            return '';
        }

        // Kung ang $dataArray ay isang string (hindi array), i-convert ito sa array
        if (is_string($dataArray)) {
            $dataArray = json_decode($dataArray, true);  // Convert the string to an array
        }

        // Kung empty ang array, magbabalik tayo ng empty string
        if (empty($dataArray)) {
            return '';
        }

        // Initialize HTML string
        $html = '<div style="margin-top: 10px;">
                    <p><strong style="color: #e76a3c;">ATTACKS</strong></p>';

        // Pag-iterate sa bawat attack data
        foreach ($dataArray as $data) {
            $damage = !empty($data['damage']) ? ' (Damage: ' . $data['damage'] . ')' : '';  // Kung may damage
            $html .= '<p style="margin-top: 0px;margin-bottom: 0px;color: #f88d67;">' . $data['name'] . $damage . '  </p>';
        }

        // I-close ang div at ibalik ang HTML
        $html .= '</div>';
        return $html;
    }
}