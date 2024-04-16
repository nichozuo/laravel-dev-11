
### 每个模块下（app/Modules)，都要放一个共用的代码
### 默认生成的控制器，都会继承BaseController

```php
<?php  
  
namespace App\Modules\Admin;  
  
use App\Enums\SysModuleEnum;  
use App\Http\Controllers\Controller;  
use App\Models\Admins;  
  
class BaseController extends Controller  
{  
    private ?Admins $user = null;  
    protected string $guardName = SysModuleEnum::Admin->value;  
  
    /**  
     * @return Admins|null  
     */    public function getUser(): ?Admins  
    {  
        if (!$this->user) {  
            $user = auth()->guard($this->guardName)->user();  
            $this->user = $user;  
        }  
        return $this->user;  
    }  
  
    /**  
     * @return string  
     */    protected function getUploadDir(): string  
    {  
        return implode('/', [  
            'manager'  
        ]);  
    }  
}
```