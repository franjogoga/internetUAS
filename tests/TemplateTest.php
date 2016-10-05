<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TemplateTest extends TestCase
{
    
    use DatabaseMigrations;


    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->assertTrue(true);
    }

    public function test_cr_are_01()
    {
        $user = factory(Intranet\Models\User::class)->make();

    	$this->actingAs($user)
            ->withSession([
	    		'actions' => [],
	    		'user' => $user
    		])->visit('/psp/templates/create')
    		->select('1', 'fase')
            ->type('Plantilla','titulo')
    		->attach('../uploads/templates/1.docx','ruta')
    		->check('obligatorio')
    		->press('Guardar')
    		->seePageIs('/psp/templates')
    		->see('Documentos',true);
    }

    public function test_cr_are_02()
    {
        $user = factory(Intranet\Models\User::class)->make();

    	$this->actingAs($user)
            ->withSession([
	    		'actions' => [],
	    		'user' => $user
    		])->visit('/psp/templates/create')
    		->select(1, 'fase')
            ->type('Plantilla3','titulo')
    		->press('Guardar')
    		->seePageIs('/psp/templates/create')
            ->see('Debe ingresar una Plantilla',true);            
    }


    public function test_cr_are_03()
    {
        $user = factory(Intranet\Models\User::class)->make();

        $this->actingAs($user)
            ->withSession([
                'actions' => [],
                'user' => $user
            ])->visit('/psp/templates/create')
            ->select(1, 'fase')
            ->attach('../uploads/templates/2.png','ruta')
            ->press('Guardar')
            ->seePageIs('/psp/templates/create')
            ->see('Debe ingresar un titulo',true);            
    }

}
