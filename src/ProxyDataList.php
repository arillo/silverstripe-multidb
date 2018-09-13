<?php
namespace Arillo\MultiDB;

use SilverStripe\ORM\ArrayList;

class ProxyDataList extends ArrayList
{
    protected $proxyClass = null;

    public function __construct(string $proxyClass)
    {
        $this->proxyClass = $proxyClass;

        $items = $proxyClass::db_conn()
            ->select(
                $proxyClass::table_name(),
                $proxyClass::all_fields()
            )
        ;

        parent::__construct();

        foreach ($items as $item)
        {
            $this->push($proxyClass::create($item));
        }
    }
}
