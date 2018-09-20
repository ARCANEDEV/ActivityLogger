<?php namespace Arcanedev\ActivityLogger\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class     Article
 *
 * @package  Arcanedev\ActivityLogger\Tests\Models
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 */
class Article extends Model
{
    /* -----------------------------------------------------------------
     |  Properties
     | -----------------------------------------------------------------
     */

    protected $table = 'articles';

    protected $guarded = [];

    /* -----------------------------------------------------------------
     |  Relationships
     | -----------------------------------------------------------------
     */

    public function User()
    {
        return $this->belongsTo(User::class);
    }
}
