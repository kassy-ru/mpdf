<?php

namespace Mpdf\Barcode;

/**
 * C128 barcodes.
 * Very capable code, excellent density, high reliability; in very wide use world-wide
 */
class Code128Auto extends AbstractBarcode implements BarcodeInterface
{
    /**
     * @var string
     */
    private static $dict_a = ' !"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_';

    /**
     * @var string
     */
    private static $dict_b = ' !"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~';

    /**
     * special codes
     * @var array
     */
    private $fnc_a = [
        241 => 102,
        242 => 97,
        243 => 96,
        244 => 101
    ];

    /**
     * special codes
     * @var array
     */
    private $fnc_b = [
        241 => 102,
        242 => 97,
        243 => 96,
        244 => 100
    ];

    /**
     * @var array
     */
    private $chr = [
        '212222', /* 00 */
        '222122', /* 01 */
        '222221', /* 02 */
        '121223', /* 03 */
        '121322', /* 04 */
        '131222', /* 05 */
        '122213', /* 06 */
        '122312', /* 07 */
        '132212', /* 08 */
        '221213', /* 09 */
        '221312', /* 10 */
        '231212', /* 11 */
        '112232', /* 12 */
        '122132', /* 13 */
        '122231', /* 14 */
        '113222', /* 15 */
        '123122', /* 16 */
        '123221', /* 17 */
        '223211', /* 18 */
        '221132', /* 19 */
        '221231', /* 20 */
        '213212', /* 21 */
        '223112', /* 22 */
        '312131', /* 23 */
        '311222', /* 24 */
        '321122', /* 25 */
        '321221', /* 26 */
        '312212', /* 27 */
        '322112', /* 28 */
        '322211', /* 29 */
        '212123', /* 30 */
        '212321', /* 31 */
        '232121', /* 32 */
        '111323', /* 33 */
        '131123', /* 34 */
        '131321', /* 35 */
        '112313', /* 36 */
        '132113', /* 37 */
        '132311', /* 38 */
        '211313', /* 39 */
        '231113', /* 40 */
        '231311', /* 41 */
        '112133', /* 42 */
        '112331', /* 43 */
        '132131', /* 44 */
        '113123', /* 45 */
        '113321', /* 46 */
        '133121', /* 47 */
        '313121', /* 48 */
        '211331', /* 49 */
        '231131', /* 50 */
        '213113', /* 51 */
        '213311', /* 52 */
        '213131', /* 53 */
        '311123', /* 54 */
        '311321', /* 55 */
        '331121', /* 56 */
        '312113', /* 57 */
        '312311', /* 58 */
        '332111', /* 59 */
        '314111', /* 60 */
        '221411', /* 61 */
        '431111', /* 62 */
        '111224', /* 63 */
        '111422', /* 64 */
        '121124', /* 65 */
        '121421', /* 66 */
        '141122', /* 67 */
        '141221', /* 68 */
        '112214', /* 69 */
        '112412', /* 70 */
        '122114', /* 71 */
        '122411', /* 72 */
        '142112', /* 73 */
        '142211', /* 74 */
        '241211', /* 75 */
        '221114', /* 76 */
        '413111', /* 77 */
        '241112', /* 78 */
        '134111', /* 79 */
        '111242', /* 80 */
        '121142', /* 81 */
        '121241', /* 82 */
        '114212', /* 83 */
        '124112', /* 84 */
        '124211', /* 85 */
        '411212', /* 86 */
        '421112', /* 87 */
        '421211', /* 88 */
        '212141', /* 89 */
        '214121', /* 90 */
        '412121', /* 91 */
        '111143', /* 92 */
        '111341', /* 93 */
        '131141', /* 94 */
        '114113', /* 95 */
        '114311', /* 96 */
        '411113', /* 97 */
        '411311', /* 98 */
        '113141', /* 99 */
        '114131', /* 100 */
        '311141', /* 101 */
        '411131', /* 102 */
        '211412', /* 103 START A */
        '211214', /* 104 START B */
        '211232', /* 105 START C */
        '233111', /* STOP */
        '200000'  /* END */
    ];

    /**
     * Code128Auto constructor.
     * @param $code
     * @param string $type
     * @param bool $ean
     *
     * @throws BarcodeException
     */
    public function __construct($code, $type = 'AUTO', $ean = false)
    {
        $this->init($code, $type, $ean);
        $this->data['nom-X'] = 0.381; // Nominal value for X-dim (bar width) in mm (2 X min. spec.)
        $this->data['nom-H'] = 10;  // Nominal value for Height of Full bar in mm (non-spec.)
        $this->data['lightmL'] = 10; // LEFT light margin =  x X-dim (spec.)
        $this->data['lightmR'] = 10; // RIGHT light margin =  x X-dim (spec.)
        $this->data['lightTB'] = 0; // TOP/BOTTOM light margin =  x X-dim (non-spec.)
    }

    /**
     * @param $code
     * @param $type
     * @param $ean
     *
     * @throws BarcodeException
     */
    protected function init($code, $type, $ean)
    {
        // ASCII characters for code A (ASCII 00 - 95)
        $keys_a = self::$dict_a;
        for ($i = 0; $i <= 31; $i++) {
            $keys_a .= chr($i);
        }
        // ASCII characters for code B (ASCII 32 - 127)
        $keys_b = self::$dict_b . chr(127);

        $startid = '';
        // array of symbols
        $code_data = [];
        // length of the code
        $len = strlen($code);
        if (in_array(strtoupper($type), ["AUTO", ""])) {
            // split code into sequences
            $sequence = [];
            // get numeric sequences (if any)
            $numseq = [];
            list($sequence, $code) = $this->sequence($sequence, $numseq, $code, $len);

            // process the sequence
            foreach ($sequence as $key => $seq) {
                switch ($seq[0]) {
                    case 'A':
                        {
                            list($startid, $sequence, $code_data) = $this->TypeA(
                                $key,
                                $sequence,
                                $seq,
                                $this->fnc_a,
                                $keys_a,
                                $code_data,
                                $startid
                            );
                            break;
                        }
                    case 'B':
                        {
                            list($startid, $sequence, $code_data) = $this->TypeB(
                                $key,
                                $sequence,
                                $seq,
                                $this->fnc_a,
                                $keys_b,
                                $this->fnc_b,
                                $code_data,
                                $startid
                            );
                            break;
                        }
                    case 'C':
                        {
                            if ($key == 0) {
                                $startid = 105;
                            } elseif ($sequence[($key - 1)][0] != 'C') {
                                $code_data[] = 99;
                            }
                            for ($i = 0; $i < $seq[2]; $i += 2) {
                                $chrnum = $seq[1]{$i} . $seq[1]{$i + 1};
                                $code_data[] = intval($chrnum);
                            }
                            break;
                        }
                }
            }
        } else {
            throw new BarcodeException('Invalid CODE128 auto barcode type');
        }

        // calculate check character
        $sum = $startid;
        foreach ($code_data as $key => $val) {
            $sum += ($val * ($key + 1));
        }
        // add check character
        $code_data[] = ($sum % 103);
        // add stop sequence
        $code_data[] = 106;
        $code_data[] = 107;
        // add start code at the beginning
        array_unshift($code_data, $startid);
        // build barcode array
        $bararray = ['code' => $code, 'maxw' => 0, 'maxh' => 1, 'bcode' => []];
        foreach ($code_data as $val) {
            $seq = $this->chr[$val];
            for ($j = 0; $j < 6; ++$j) {
                if (($j % 2) == 0) {
                    $t = true; // bar
                } else {
                    $t = false; // space
                }
                $w = $seq{$j};
                $bararray['bcode'][] = ['t' => $t, 'w' => $w, 'h' => 1, 'p' => 0];
                $bararray['maxw'] += $w;
            }
        }
        $bararray['checkdigit'] = '';
        $this->data = $bararray;
    }

    /**
     * @param $sequence
     * @param $numseq
     * @param $code
     * @param $len
     *
     * @return array
     */
    private function sequence($sequence, $numseq, $code, $len)
    {
        preg_match_all('/([0-9]{4,})/', $code, $numseq, PREG_OFFSET_CAPTURE);
        if (isset($numseq[1]) && !empty($numseq[1])) {
            $end_offset = 0;
            foreach ($numseq[1] as $val) {
                $offset = $val[1];
                if ($offset > $end_offset) {
                    // non numeric sequence
                    $sequence = array_merge(
                        $sequence,
                        $this->get128ABsequence(
                            substr(
                                $code,
                                $end_offset,
                                ($offset - $end_offset)
                            )
                        )
                    );
                }
                // numeric sequence
                $slen = strlen($val[0]);
                if (($slen % 2) != 0) {
                    // the length must be even
                    --$slen;
                }
                $sequence[] = ['C', substr($code, $offset, $slen), $slen];
                $end_offset = $offset + $slen;
            }
            if ($end_offset < $len) {
                $sequence = array_merge($sequence, $this->get128ABsequence(substr($code, $end_offset)));
            }
        } else {
            // text code (non C mode)
            $sequence = array_merge($sequence, $this->get128ABsequence($code));
        }
        return [$sequence, $code];
    }

    /**
     * @param $key
     * @param $sequence
     * @param $seq
     * @param $fnc_a
     * @param $keys_b
     * @param $fnc_b
     * @param $code_data
     * @param $startid
     *
     * @return array
     */
    private function TypeB($key, $sequence, $seq, $fnc_a, $keys_b, $fnc_b, $code_data, $startid)
    {
        if ($key == 0) {
            $tmpchr = ord($seq[1][0]);
            if (($seq[2] == 1) && ($tmpchr >= 241) && ($tmpchr <= 244)
                && isset($sequence[($key + 1)]) && ($sequence[($key + 1)][0] != 'B')) {
                switch ($sequence[($key + 1)][0]) {
                    case 'A':
                        {
                            $startid = 103;
                            $sequence[$key][0] = 'A';
                            $code_data[] = $fnc_a[$tmpchr];
                            break;
                        }
                    case 'C':
                        {
                            $startid = 105;
                            $sequence[$key][0] = 'C';
                            $code_data[] = $fnc_a[$tmpchr];
                            break;
                        }
                }
                return [$startid, $sequence, $code_data, $seq];

            } else {
                $startid = 104;
            }
        } elseif ($sequence[($key - 1)][0] != 'B') {
            if (($seq[2] == 1)
                && ($key > 0)
                && ($sequence[($key - 1)][0] == 'A')
                && (!isset($sequence[($key - 1)][3]))) {
                // single character shift
                $code_data[] = 98;
                // mark shift
                $sequence[$key][3] = true;
            } elseif (!isset($sequence[($key - 1)][3])) {
                $code_data[] = 100;
            }
        }
        for ($i = 0; $i < $seq[2]; ++$i) {
            $char = $seq[1]{$i};
            $char_id = ord($char);
            if (($char_id >= 241) && ($char_id <= 244)) {
                $code_data[] = $fnc_b[$char_id];
            } else {
                $code_data[] = strpos($keys_b, $char);
            }
        }
        return [$startid, $sequence, $code_data, $seq];
    }

    /**
     * @param $key
     * @param $sequence
     * @param $seq
     * @param $fnc_a
     * @param $keys_a
     * @param $code_data
     * @param $startid
     *
     * @return array
     */
    private function TypeA($key, $sequence, $seq, $fnc_a, $keys_a, $code_data, $startid)
    {
        if ($key == 0) {
            $startid = 103;
        } elseif ($sequence[($key - 1)][0] != 'A') {
            if (($seq[2] == 1) && ($key > 0) && ($sequence[($key - 1)][0] == 'B')
                && (!isset($sequence[($key - 1)][3]))) {
                // single character shift
                $code_data[] = 98;
                // mark shift
                $sequence[$key][3] = true;
            } elseif (!isset($sequence[($key - 1)][3])) {
                $code_data[] = 101;
            }
        }
        for ($i = 0; $i < $seq[2]; ++$i) {
            $char = $seq[1]{$i};
            $char_id = ord($char);
            if (($char_id >= 241) && ($char_id <= 244)) {
                $code_data[] = $fnc_a[$char_id];
            } else {
                $code_data[] = strpos($keys_a, $char);
            }
        }
        return [$startid, $sequence, $code_data, $seq];
    }

    /**
     * Split text code in A/B sequence for 128 code
     * @param $code (string) code to split.
     * @return array sequence
     */
    protected function get128ABsequence($code)
    {
        $len = strlen($code);
        $sequence = array();
        // get A sequences (if any)
        $numseq = array();
        preg_match_all('/([\0-\31])/', $code, $numseq, PREG_OFFSET_CAPTURE);
        if (isset($numseq[1]) AND !empty($numseq[1])) {
            $end_offset = 0;
            foreach ($numseq[1] as $val) {
                $offset = $val[1];
                if ($offset > $end_offset) {
                    // B sequence
                    $sequence[] = array('B', substr($code, $end_offset, ($offset - $end_offset)), ($offset - $end_offset));
                }
                // A sequence
                $slen = strlen($val[0]);
                $sequence[] = array('A', substr($code, $offset, $slen), $slen);
                $end_offset = $offset + $slen;
            }
            if ($end_offset < $len) {
                $sequence[] = array('B', substr($code, $end_offset), ($len - $end_offset));
            }
        } else {
            // only B sequence
            $sequence[] = array('B', $code, $len);
        }
        return $sequence;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return 'CODE128AUTO';
    }
}
