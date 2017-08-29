<?php
declare(strict_types = 1);

use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/../../../lib/config/sfConfigHandler.class.php');
require_once(__DIR__ . '/../../../lib/util/sfToolkit.class.php');

/**
 * @covers sfConfigHandler
 */
class sfConfigHandlerTest extends TestCase
{
    /**
     * @dataProvider replaceConstantsProvider
     */
    public function testReplaceConstants($input, $output)
    {
        sfConfig::clear();
        sfConfig::set('foo', 'bar');
        self::assertSame($output, sfConfigHandler::replaceConstants($input));
    }

    public function replaceConstantsProvider()
    {
        return [
            ['a', 'a'],
            [[], []],
            [[[['a']]], [[['a']]]],
            ['%foo%', 'bar'],
            ['%foo% foo %foo%', 'bar foo bar'],
            ['%unknown%', '%unknown%'],
            [['%foo%'], ['bar']],
        ];
    }

    /**
     * @dataProvider replacePathProvider
     */
    public function testReplacePath($input, $output)
    {
        sfConfig::clear();
        sfConfig::set('sf_app_dir', '/the/app/dir');
        self::assertSame($output, sfConfigHandler::replacePath($input));
    }

    public function replacePathProvider()
    {
        return [
            ['a', '/the/app/dir/a'],
            ['/a', '/a'],
            [['a'], ['/the/app/dir/a']],
            [['/a'], ['/a']],
        ];
    }
}
