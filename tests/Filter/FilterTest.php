<?php

namespace Filter;

class FilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Filter\Filter $filter */
    private $filter;

    public function setUp() {
        $this->filter = new Filter;
        $this->filter->registerDefaultFilters();
    }

	public function testDefaultRulesForMissingValues() {
		$result = $this->filter->filter(
			['key' => 'value'],
			['expected_key' => 'default:expected_value']
		);
			
		$this->assertEquals(2, sizeof($result));
		$this->assertTrue(isset($result['expected_key']));
		$this->assertEquals('expected_value', $result['expected_key']);
		$this->assertEquals('value', $result['key']);
	}

	public function testResultForDisabledExecutionOfNonVisitedRules() {
		$result = $this->filter->filter(
			['key' => 'value'],
			['expected_key' => 'default:expected_value'],
			false
		);
			
		$this->assertEquals(1, sizeof($result));
		$this->assertTrue(isset($result['key']));
	}

	public function testEmptyRulesDoesNotBreakFilter() {
		$result = $this->filter->filter(['key' => 'data', 'key2' => 'data2'], ['key' => '', 'key2' => null]);
		
		$this->assertEquals(['key' => 'data', 'key2' => 'data2'], $result);
	}
	
	public function testFilterChainingForDefaultRules() {
		$this->assertEquals("TEST", $this->filter->filterOne('default:test|upper', null));
	}
	
    public function testSingleRule() {
        $this->assertEquals('tEST', $this->filter->filterOne('upper|lowerfirst', 'Test'));
    }

    public function testNonExistantRule() {
        $this->setExpectedException('Exception', "No filter named 'dummy' registered");
        $this->filter->filterOne('dummy', 'test');
    }

    public function testUppercase() {
        $this->assertEquals('TEST', $this->filter->filterOne('upper', 'teSt'));
    }

    public function testLowercase() {
        $this->assertEquals('test', $this->filter->filterOne('lower', 'teSt'));
    }

    public function testCapitalize() {
        $this->assertEquals('TeSt', $this->filter->filterOne('capfirst', 'teSt'));
    }

    public function testUncapfirst() {
        $this->assertEquals('tEsT', $this->filter->filterOne('lowerfirst', 'tEsT'));
    }

    public function testTrim() {
        $this->assertEquals("asdf", $this->filter->filterOne('trim', "\n\t  asdf  \n\t"));
    }

    public function testTrimArgs() {
		$this->assertEquals('asdf', $this->filter->filterOne('trim:#,$', '$asdf#'));
		$this->assertEquals('asdf', $this->filter->filterOne('trim:.,","', 'asdf..,.'));
    }

    public function testLeftTrim() {
        $this->assertEquals("asdf  \n\t", $this->filter->filterOne('ltrim', "\n\t  asdf  \n\t"));
    }

    public function testLeftTrimArgs() {
        $this->assertEquals('asdf#', $this->filter->filterOne('ltrim:#,$', '$asdf#'));
    }

    public function testRightTrim() {
        $this->assertEquals("\n\t  asdf", $this->filter->filterOne('rtrim', "\n\t  asdf  \n\t"));
    }

    public function testRightTrimArgs() {
        $this->assertEquals('$asdf', $this->filter->filterOne('rtrim:#,$', '$asdf#'));
    }
	
	public function testDefault() {
		$this->assertEquals('my_default', $this->filter->filterOne('default:my_default', null));
	}

	public function testDefaultBoolean() {
		$this->assertEquals(true, $this->filter->filterOne('default_boolean:true', null));
	}

	public function testDefaultEmptyArray() {
		$this->assertEquals([], $this->filter->filterOne('default_array', null));
	}

	public function testDefaultNonEmptyArray() {
		$this->assertEquals(['first', 'second'], $this->filter->filterOne('default_array:first,second', null));
	}

	public function testConvertDate() {
		$this->assertEquals('2015-07-27', $this->filter->filterOne('convert_date:d.m.Y,Y-m-d', '27.07.2015'));
	}

	public function testOptionalDate() {
		$this->assertEquals(null, $this->filter->filterOne('default|convert_date:d.m.Y,Y-m-d', null));
	}
	
    public function testOrdering() {
        $this->assertEquals('tEST', $this->filter->filterOne('lower|upper|lowerfirst', 'test'));
    }

    public function testCustomFilter() {
        $this->assertNotContains('reverse', $this->filter->getFilters());

        $reverse = function($string) {
            return strrev($string);
        };
        $this->filter->registerFilter('reverse', $reverse);

        $this->assertEquals('dlrow olleh', $this->filter->filterOne('reverse', 'hello world'));
    }

    public function testRegisterFilterTwice() {
        $this->filter->registerFilter('dummy', function($str) { return $str; });
        $this->setExpectedException('Exception', "Filter named 'dummy' already registered");
        $this->filter->registerFilter('dummy', function($str) { return $str; });
    }

    public function testUnregisterFilter() {
        $this->assertContains('trim', $this->filter->getFilters());
        $this->filter->unregisterFilter('trim');
        $this->assertNotContains('trim', $this->filter->getFilters());
    }

    public function testRegisterUncallableFilter() {
        $this->setExpectedException('Exception', "Filter should be callable");
        $this->filter->registerFilter('uninvokable', new \stdClass);
    }

    public function testRegisterFilterByClassName() {
        $this->filter->registerFilter('by_class_name', 'Filter\TestFilter');
        $this->assertEquals('TestFilter', $this->filter->filterOne('by_class_name', 'test'));
    }
}

class TestFilter
{
    public function __invoke($str) {
        return 'TestFilter';
    }
}