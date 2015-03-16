<?php
/**
 * @author alpaca
 * @copyright 2015 alpacagm@gmail.com
 * @package TriggersUnserializer
 */

namespace alpaca\TriggersUnserializer\test;

use alpaca\TriggersUnserializer\TriggerUnserializer;

class TriggerUnserializerTest extends \PHPUnit_Framework_TestCase {
    public function testSimple()
    {
        $encoded = 'id:52,val:~,sval:9:qwe\'"asde,fval:3.14159';
        $decoded = [
            'id'=> 52,
            'val'=>null,
            'sval'=>'qwe\'"asde',
            'fval'=>3.14159,
        ];
        static::assertEquals($decoded, TriggerUnserializer::Decode($encoded));
    }

    public function testTailString()
    {
        $encoded = 'id:52,val:~,sval:9:qwe\'"asde';
        $decoded = [
            'id'=> 52,
            'val'=>null,
            'sval'=>'qwe\'"asde',
        ];
        static::assertEquals($decoded, TriggerUnserializer::Decode($encoded));
    }

    public function testUnderflowError()
    {
        $encoded = 'id:52,val:~,sval:9:qwe';
        self::setExpectedException('UnexpectedValueException');
        TriggerUnserializer::Decode($encoded);
    }

    public function testFormatError()
    {
        $encoded = 'id:52,val:~,sval:9:qweekjh,fval:3.14159';
        self::setExpectedException('UnexpectedValueException');
        TriggerUnserializer::Decode($encoded);
    }
}
