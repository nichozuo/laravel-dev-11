```php
<?php  
  
namespace Tests;  
  
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;  
use LaravelDev\Traits\TestCaseTrait;  
  
abstract class TestCase extends BaseTestCase  
{  
    use TestCaseTrait;  
  
    protected function setUp(): void  
    {  
        parent::setUp();  
  
        $this->tokens = [  
            'Admin' => '1|foRhxkugzMZCYU6Dww56lLNf8WlkOfI0AR1Yhamme7731300',  
            'Company' => '2|9B7nXKR5CPsSlOeMhwEer8ynYC5giFFlOIuMvFp294fe2bd0',  
        ];  
    }  
}
```

### Tokens 里保存对应模块的token