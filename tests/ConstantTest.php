<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class ConstantTest extends PHPUnit_Framework_TestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

    /** @var TestConfig */
    protected static $config;

    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    /**
     * @return void
     */
    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        self::$config = new TestConfig();
    }

    /**
     * @return void
     */
    public function setUp()
    {
        FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
        $this->project_checker->setConfig(self::$config);
    }

    /**
     * @return void
     */
    public function testConstantInFunction()
    {
        $stmts = self::$parser->parse('<?php
        useTest();
        const TEST = 2;

        function useTest() : int {
            return TEST;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testConstantInClosure()
    {
        $stmts = self::$parser->parse('<?php
        const TEST = 2;
        
        $useTest = function() : int {
            return TEST;
        };
        $useTest();
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testConstantDefinedInFunction()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @return void
         */
        function defineConstant() {
            define("CONSTANT", 1);
        }

        defineConstant();

        echo CONSTANT;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedConstant
     * @return                   void
     */
    public function testConstantDefinedInFunctionButNotCalled()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @return void
         */
        function defineConstant() {
            define("CONSTANT", 1);
        }

        echo CONSTANT;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
