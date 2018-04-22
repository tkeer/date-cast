## Why
Ever wanted to show different date format on the frontend but same in Y-m-d in the db? You can 
easily manage date formats using DateCast. You don't need to write accessors/mutators for 
every field anymore.


## How it works
use trait ``DateCast`` in you model class

```
    protected $dates_to_cast = [
        scheduled_at
    ];
    
    
    $this->scheduled_at = 04/22/2018;
    
    //this will will stored in Y-m-d format in db
    //dumping this field will results in m/d/Y format
    
    dump($this->scheduled_at)
    04/22/2018
    
```

### Auto parse
If you want auto detect format

``protected $auto_parse_dates = true;``



### Different format for every field
If your date format is not detectable by Carbon or you want different format for every field, 
you can use ``$dates_cast_from_formats`` property.

```
    protected $dates_cast_from_formats = [
        scheduled_at => 'd/m/Y'
    ];

```