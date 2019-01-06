<!---->
<!--    private static $bytetoB64 = [-->
<!--        "a", "b", "c", "d", "e", "f", "g", "h",-->
<!--        "i", "j", "k", "l", "m", "n", "o", "p",-->
<!--        "q", "r", "s", "t", "u", "v", "w", "x",-->
<!--        "y", "z", "A", "B", "C", "D", "E", "F",-->
<!--        "G", "H", "I", "J", "K", "L", "M", "N",-->
<!--        "O", "P", "Q", "R", "S", "T", "U", "V",-->
<!--        "W", "X", "Y", "Z", "0", "1", "2", "3",-->
<!--        "4", "5", "6", "7", "8", "9", "(", ")"];-->
<!---->
<!--    private static $B64tobyte = [-->
<!--        'a' => 0, 'b' => 1, 'c' => 2, 'd' => 3, 'e' => 4, 'f' => 5,-->
<!--        'g' => 6, 'h' => 7, 'i' => 8, 'j' => 9, 'k' => 10, 'l' => 11,-->
<!--        'm' => 12, 'n' => 13, 'o' => 14, 'p' => 15, 'q' => 16, 'r' => 17,-->
<!--        's' => 18, 't' => 19, 'u' => 20, 'v' => 21, 'w' => 22, 'x' => 23,-->
<!--        'y' => 24, 'z' => 25,-->
<!---->
<!--        'A' => 26, 'B' => 27, 'C' => 28, 'D' => 29, 'E' => 30, 'F' => 31,-->
<!--        'G' => 32, 'H' => 33, 'I' => 34, 'J' => 35, 'K' => 36, 'L' => 37,-->
<!--        'M' => 38, 'N' => 39, 'O' => 40, 'P' => 41, 'Q' => 42, 'R' => 43,-->
<!--        'S' => 44, 'T' => 45, 'U' => 46, 'V' => 47, 'W' => 48, 'X' => 49,-->
<!--        'Y' => 50, 'Z' => 51,-->
<!---->
<!--        '0' => 52, '1' => 53, '2' => 54, '3' => 55, '4' => 56, '5' => 57,-->
<!--        '6' => 58, '7' => 59, '8' => 60, '9' => 61, '(' => 62, ')' => 63,-->
<!--    ];-->
<!---->
<!--    /**-->
<!--     * @param $decompressedString-->
<!--     * @return mixed-->
<!--     */-->
<!--    private function _deserialize($decompressedString){-->
<!--        $lua = new \Lua();-->
<!--        // $lua->assign("php_var", array(1=>1, 2, 3)); //lua table index begin with 1-->
<!--        $lua->eval(-->
<!--            file_get_contents(base_path('app/Logic/MDT/LibStub.lua')) .-->
<!--            file_get_contents(base_path('app/Logic/MDT/LibCompress.lua')) .-->
<!--            file_get_contents(base_path('app/Logic/MDT/AceSerializer.lua'))-->
<!--        );-->
<!--        $test = ['testy' => 'this is a test array'];-->
<!--        $serializedTest = $lua->call("Serialize", [$test]);-->
<!--        $result = $lua->call("Deserialize", [$serializedTest]);-->
<!--        // $result = $lua->call("Deserialize", [$decompressedString]);-->
<!---->
<!--        return $serializedTest;-->
<!--    }-->
<!---->
<!--    /**-->
<!--     * @param $decodedB64String-->
<!--     * @return string-->
<!--     */-->
<!--    private function _decompress($decodedB64String)-->
<!--    {-->
<!--        $huffman = new Huffman();-->
<!--        $huffman->generateDictionary($decodedB64String);-->
<!--        return $huffman->decode($decodedB64String);-->
<!--    }-->
<!---->
<!--    /**-->
<!--     * Decodes a B64 string into a readable string-->
<!--     * @param $string-->
<!--     * @return string-->
<!--     */-->
<!--    private function _decodeB64($string)-->
<!--    {-->
<!--        $bit8 = [];-->
<!--        $decoded_size = 0;-->
<!--        $i = 0;-->
<!--        $bitfield_len = 0;-->
<!--        $bitfield = 0;-->
<!--        $strLen = strlen($string);-->
<!---->
<!--        while ($i < $strLen) {-->
<!--            if ($bitfield_len >= 8) {-->
<!--                $decoded_size++;-->
<!--                $bit8[$decoded_size] = (string)($bitfield & 255);-->
<!--                $bitfield = $bitfield >> 8;-->
<!--                $bitfield_len -= 8;-->
<!--            }-->
<!---->
<!--            $char = self::$B64tobyte[substr($string, $i, 1)];-->
<!--            $bitfield += $char << $bitfield_len;-->
<!--            $bitfield_len += 6;-->
<!--            $i++;-->
<!--        }-->
<!---->
<!--        return implode('', $bit8);-->
<!--    }-->