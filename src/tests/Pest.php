<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
| Uji Feature menyalakan aplikasi dan basis data (SQLite di memori).
| Uji Unit tidak menyentuh keduanya: PHP murni, selesai dalam milidetik.
*/
pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');
