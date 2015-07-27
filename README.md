# Filter [![Latest Stable Version](https://poser.pugx.org/rmasters/filter/v/stable.png)](https://packagist.org/packages/rmasters/filter) [![master](https://travis-ci.org/rmasters/filter.png?branch=master)](https://travis-ci.org/rmasters/filter) [![Coverage Status](https://coveralls.io/repos/rmasters/filter/badge.png)](https://coveralls.io/r/rmasters/filter) [![versioneye dependencies](https://www.versioneye.com/user/projects/5217bad8632bac775003b749/badge.png)](https://www.versioneye.com/user/projects/5217bad8632bac775003b749)

Aims to help make filtering input to your Eloquent models easier.

Simplifies code like this:

    class Address extends Model {
        public function setPostcodeAttribute($value) {
            $this->attributes['postcode'] = strtoupper(trim($value));
        }

        public function setCityAttribute($value) {
            $this->attributes['city'] = trim($value);
        }

        public function getCityAttribute($value) {
            return strtoupper($value);
        }
    }

Into this:

    class Address extends Model {
        use Filter\HasFilters

        protected $input = [
            'postcode' => 'uppercase|trim',
            'city' => 'trim'
        ];

        protected $output = [
            'city' => 'uppercase'
        ];
    }

Can also be used standalone:

    $clean = Filter::filter(['city' => 'London'], ['city' => 'trim|uppercase']);

## Installation

Installable via composer:

    "rmasters/filter": "dev-master",

### Laravel 4/5

To use the model trait and service for Laravel 4 and Laravel 5, add the following lines to
`config/app.php`:

    'providers' => array(
        // ...
        'Filter\FilterServiceProvider',

    'aliases' => array(
        // ...
        'Filter' => 'Filter\Facades\Filter',

## Usage

> Examples below use the Facade style (`Filter::filter()`) for brevity -
standalone users should expand this to `$filter->filter()`.

The standalone class is similar to Laravel's validator component:

    $filtered = Filter::filter(['name' => 'Ross'], ['name' => 'trim']);
    $value = Filter::filterOne('Ross', 'trim');

Rules are also constructed similarly to Validator:

    Filter::filterOne('test', 'trim|upper');
    Filter::filterOne('test...', 'rtrim:.');
    Filter::filterOne('test', ['trim', 'upper']);

Filters are run sequentially from left to right. Arguments are parsed by
[`str_getcsv`](http://php.net/str_getcsv) - e.g. to trim commas use `trim:","`.

### Default values for missing keys or empty values of keys

For form parsing it is sometimes necessary to get default values for keys even if the keys are not present or the values of the keys are empty. 
The assignment of default values can be achieved by using the `default*` filters:

    $clean = Filter::filter(['city' => 'London'], ['city' => 'trim|uppercase', 'required_key' => 'default:value|uppercase]);
	// $clean == ['city' => 'LONDON', 'required_key' => 'VALUE'];

    $clean = Filter::filter([], ['required_key' => 'default']);
	// $clean == ['required_key' => ''];

    $clean = Filter::filter([], ['required_boolean' => 'default_boolean']);
	// $clean == ['required_boolean' => false];

    $clean = Filter::filter([], ['required_boolean' => 'default_boolean:true']);
	// $clean == ['required_boolean' => true];

	$clean = Filter::filter([], ['required_array' => 'default_array']);
	// $clean == ['required_array' => []]

	$clean = Filter::filter([], ['required_array' => 'default_array:val1,val2']);
	// $clean == ['required_array' => ['val1', 'val2']]
	
The behavior for parsing missing or empty keys can be disabled by passing a `false` flag as third parameter:

	Filter::filter(['key' => 'value'], ['key' => 'trim'], false);
	
### Registering filters

A filter is a callable that accepts the input string and an array of arguments:

    Filter::register('slugify', function($str, array $args) {
        return preg_replace('/[^a-z0-9]+/', '-', strtolower($str));
    });

Other callable values are classes that define an `__invoke` method and function
names. For example, Zend Framework's filters all implement `__invoke`, so
`'Zend\I18n\Filter\Alnum'` is a valid callable.

Filters can be unregistered using `Filter::unregister('slugify')`.

#### Default filters

By default the following filters are registered:

    trim        trim($str)
    trim:|,/    trim($str, '|/');
    ltrim       ltrim($str)
    ltrim:|,/   ltrim($str, '|/');
    rtrim       rtrim($str)
    rtrim:|,/   rtrim($str, '|/');
    upper       strtoupper($str)
    lower       strtolower($str)
    capfirst    ucfirst($str)
    lowerfirst  lcfirst($str)
	default		*string|double|int* | ""
	default_boolean	*boolean* | false
	default_array	*array* | []
	convert_date from_format,to_format[,timezone = 'UTC']

### Laravel 4

A trait, `HasFilters` is available that modifies `getAttribute` (accessor) and
`setAttribute` (mutator) to apply filters to the input or output value.

These filter rules are specified in properties on the model, `$input` and
`$output` for mutators and accessors respectively.

    class Address extends Model {
        use HasFilters;

        public $fillable = ['line1', 'line2', 'line3', 'city', 'postcode'];
        public $input = [
            'line1' => 'trim',
            'line2' => 'trim',
            'line3' => 'trim',
            'city' => 'trim',
            'postcode' => 'uppercase|trim',
        ];
        public $output = [
            'city' => 'uppercase', // Uppercase only for display
        ];
    }

The filter instance is available using `App::make('filter')`, or via the facade
`Filter` depending on your setup in `config/app.php`.

#### Call chain

You can still write your own accessors or mutators which will be applied as well
as any filters that have been set. The following chains happen:

*   Mutator: `$model->name = 'Ross'` (filters applied **before** your mutator)
    1.  `Filter\HasFilters::setAttribute`
    2.  `Eloquent\Model::setAttribute`
    3.  `Your\Model::setNameAttribute` (if defined)
*   Accessor: `echo $model->name` (filters applied **after** your accessor)
    1.  `Eloquent\Model::getAttribute`
    2.  `Your\Model::getNameAttribute`
    3.  `Filter\HasFilters::getAttribute`

You should not need to modify your mutators (they should still store the value
in `$this->attributes[$name]`.

## License

Released under the MIT license.
