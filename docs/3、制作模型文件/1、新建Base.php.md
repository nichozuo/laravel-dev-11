## 模型文件分 Model 和 BaseModel
- Model继承于BaseModel
- BaseModel继承于Base

## Base文件
- 全局共用的scope可以写在这里！
- @method也可以统一放在这里，编辑器中显示会比较友好！

```
<?php  
  
namespace App\Models\Base;  
  
use App\Models\Companies;  
use Illuminate\Database\Eloquent\Factories\HasFactory;  
use Illuminate\Database\Eloquent\Model;  
use LaravelDev\Traits\ModelTrait;  
  
/**  
 * @method static ifCompany(Companies $company)  
 */
 class Base extends Model  
{  
    use HasFactory, ModelTrait;  
  
    /**  
     * @param $query  
     * @param Companies $company  
     * @return void  
     */    public function scopeIfCompany($query, Companies $company): void  
    {  
        $query->where('companies_id', $company->id);  
    }  
}
```
