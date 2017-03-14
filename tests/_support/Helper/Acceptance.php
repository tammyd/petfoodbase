<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I



class Acceptance extends \Codeception\Module
{
    static $brandsPerPage = 20;

    public static function getPageStartIndex($page) {
        return ($page - 1) * self::$brandsPerPage;
    }

    public static function getPageEndIndex($page) {
        return self::getPageStartIndex($page) + self::$brandsPerPage - 1;
    }

    public static function getBrandList() {
        //console.php info listBrands
        $brands = [
                0 => '4Health',
                1 => '9Lives',
                2 => 'Abound',
                3 => 'Acana',
                4 => 'Addiction',
                5 => 'Adirondack',
                6 => 'Against The Grain',
                7 => 'Annamaet',
                8 => 'Applaws',
                9 => 'Artemis',
                10 => 'Authority',
                11 => 'AvoDerm',
                12 => 'Best Breed',
                13 => 'bff',
                14 => 'Blackwood',
                15 => 'BLUE Buffalo',
                16 => 'Bravo',
                17 => 'By Nature',
                18 => 'Canidae',
                19 => 'Castor & Pollux',
                20 => 'Chicken Soup for the Soul',
                21 => 'Dave\'s',
                22 => 'Diamond',
                23 => 'Dr Tim\'s',
                24 => 'Eagle Pack',
                25 => 'Earthborn Holistic',
                26 => 'Evangers',
                27 => 'Evo',
                28 => 'Evolve',
                29 => 'Fancy Feast',
                30 => 'Farmina',
                31 => 'FirstMate',
                32 => 'Freshpet',
                33 => 'Friskies',
                34 => 'Fromm',
                35 => 'Fussie Cat',
                36 => 'Good Natured',
                37 => 'Goodlife',
                38 => 'Grandma Mae\'s',
                39 => 'Halo',
                40 => 'Health Extension',
                41 => 'Hi-Tor Veterinary Select',
                42 => 'Hill\'s Healthy Advantage',
                43 => 'Hill\'s Ideal Balance',
                44 => 'Hill\'s Prescription Diet',
                45 => 'Hill\'s Science Diet',
                46 => 'Holistic Blend',
                47 => 'Holistic Select',
                48 => 'Hound & Gatos',
                49 => 'I and love and you',
                50 => 'I Luv My Cat',
                51 => 'Iams',
                52 => 'Koha',
                53 => 'Lotus',
                54 => 'Meow Mix',
                55 => 'Merrick',
                56 => 'Mio9',
                57 => 'Muenster',
                58 => 'Muse',
                59 => 'Natural Balance',
                60 => 'Nature\'s Logic',
                61 => 'Nature\'s Recipe',
                62 => 'Nature\'s Variety',
                63 => 'Newman\'s Own',
                64 => 'Nulo',
                65 => 'Nutram',
                66 => 'Nutreco',
                67 => 'Nutrisca',
                68 => 'NutriSource',
                69 => 'Nutro',
                70 => 'Only Natural Pet',
                71 => 'Open Farm',
                72 => 'Orijen',
                73 => 'Oven Baked Tradition',
                74 => 'Party Animal',
                75 => 'Performatrin',
                76 => 'Petcurean',
                77 => 'PetGuard',
                78 => 'Petite Cuisine',
                79 => 'Pinnacle',
                80 => 'Precise',
                81 => 'Primal',
                82 => 'Pro Pac',
                83 => 'Pure Harmony',
                84 => 'PureVita',
                85 => 'Purina Beyond',
                86 => 'Purina Cat Chow',
                87 => 'Purina ONE',
                88 => 'Purina Pro Plan',
                89 => 'Purina Pro Plan Veterinary Diets',
//                90 => 'Rachael Ray™ Nutrish',
                91 => 'Royal Canin',
                92 => 'Satori',
                93 => 'Sheba',
                94 => 'Simply Nourish',
                95 => 'Solid Gold',
                96 => 'Soulistic',
                97 => 'SPORTMiX',
                98 => 'Stella & Chewys',
                99 => 'Taste Of The Wild',
                100 => 'Techni-Cal',
                101 => 'The Honest Kitchen',
                102 => 'Tiki Cat',
                103 => 'Timberwolf',
                104 => 'Triumph',
                105 => 'Under The Sun',
                106 => 'Variety',
                107 => 'VeRUS',
                108 => 'Victor',
                109 => 'Wellness',
                110 => 'Wellness CORE',
                111 => 'Wellness TruFood',
                112 => 'Weruva',
                113 => 'Whiskas',
                114 => 'Whole Earth Farms',
                115 => 'Wild Calling',
                116 => 'Wysong',
                117 => 'ZiwiPeak'
            ];
        
        return $brands;
    }
}
