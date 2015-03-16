<?php
/**
 * @author    alpaca
 * @copyright 2015 alpacagm@gmail.com
 * @package   OpLogger
 */

namespace alpaca\TriggersUnserializer;

use UnexpectedValueException;

class TriggerUnserializer
{
    protected static function GuessType($value)
    {
        if ($value !== '' and $value[0] === '\'') {
            return substr($value, 1, strlen($value) - 2);
        }
        if (is_numeric($value)) {
            return (float)$value;
        }
        if ($value === '~') {
            return null;
        }
        return $value;
    }

    public static function Decode($serialized)
    {
        return self::DecodeV2($serialized);
    }

    const M_VAR  = 0;
    const M_VAL  = 1;

    public static function DecodeV2($serialized)
    {
        $res   = [];
        $caret = 0;
        $l     = 0;
        $var   = null;
        $val   = null;
        $mode  = self::M_VAR;

        $fullLen = strlen($serialized);
        while ($caret < $fullLen + 1) {
            $c = ($caret === $fullLen)
                ?null
                :$serialized[$caret];
            switch ($mode) {
                case self::M_VAR:
                    if ($c === ':') {
                        $var  = substr($serialized, $l, $caret - $l);
                        $mode = self::M_VAL;
                        $l    = $caret + 1;
                    }
                    break;
                case self::M_VAL:
                    if ($c === ',' or $c === null) {
                        $res[$var] = self::GuessType(substr($serialized, $l, $caret - $l));
                        if ($res[$var] === false) {
                            $res[$var] = '';
                        }
                        $l         = $caret + 1;
                        $mode      = self::M_VAR;
                    } elseif ($c === ':') {
                        $sLen = (int)substr($serialized, $l, $caret - $l);
                        if ($caret + $sLen > $fullLen) {
                            throw new UnexpectedValueException('Invalid serialized data (sLen)', 4);
                        }
                        $nextPos = $caret + $sLen + 1;
                        if ($nextPos < $fullLen and $serialized[$nextPos] !== ','){
                            throw new UnexpectedValueException('Invalid serialized data (afterComma)', 5);
                        }
                        $res[$var] = substr($serialized, $caret + 1, $sLen);
                        $caret += $sLen + 2;
                        $l    = $caret;
                        $mode = self::M_VAR;
                        continue 2;
                    }
                    break;
            }
            ++$caret;
        }
        if ($mode !== self::M_VAR or $l !== $caret) {
            throw new UnexpectedValueException('Invalid serialized data (tail)', 3);
        }
        return $res;
    }

    public static function DecodeV1($serialized)
    {
        $res    = [];
        $chunks = explode(',', $serialized);
        while (count($chunks)) {
            $chunk    = array_shift($chunks);
            $parts    = explode(':', $chunk);
            $partsLen = count($parts);
            if ($partsLen === 2) {
                $res[$parts[0]] = self::GuessType($parts[1]);
            } elseif ($partsLen < 2) {
                throw new UnexpectedValueException('Invalid serialized data (unknown)', 1);
            } else {
                $dataLen = (int)$parts[1];
                $next    = '';
                while ($dataLen > strlen($parts[2])) {
                    if ($next === '') {
                        if (!count($chunks)) {
                            throw new UnexpectedValueException('Invalid serialized data (tail)', 2);
                        }
                        $next = array_shift($chunks);
                        $parts[2] .= ',';
                    }
                    $toEat = $dataLen - strlen($parts[2]);
                    $parts[2] .= substr($next, 0, $toEat);
                    $next = substr($next, $toEat);
                    if (false === $next) {
                        $next = '';
                    }
                }
                if ($next !== '') {
                    array_unshift($chunks, $next);
                }
                $res[$parts[0]] = $parts[2];
            }
        }
        return $res;
    }
}