<?php 

namespace Intranet\Http\Controllers\Template;

use Illuminate\Http\Request;
use Auth;
use Intranet\Http\Requests;
use Intranet\Http\Controllers\Controller;
use Intranet\Models\Template;
use Intranet\Http\Requests\TemplateRequest;

class TemplateController extends Controller
{

    public function __construct() {

    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $templates = Template::get();

        $data = [
            'templates'    =>  $templates,
        ];
        return view('template.index', $data);
        //return view('template.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('template.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(TemplateRequest $request)
    {
        try {
            $template = new Template;
            $template->idPhase       = $request['fase'];            
            $template->idTipoEstado  = 1;
            $template->idProfesor  = Auth::User()->IdUsuario;
            $template->idSupervisor  = null;
            $template->idAdmin  = null;
            $template->titulo  = $request['titulo'];
            if($request['obligatorio']==true)
                $template->obligatorio  = 1;
            else
                $template->obligatorio  = 2;
            $template->save();
            if(isset($request['ruta']) && $request['ruta'] != ""){
                $destinationPath = 'uploads/templates/'; // upload path
                $extension = $request['ruta']->getClientOriginalExtension();
                $filename = $template->id.'.'.$extension; 
                $request['ruta']->move($destinationPath, $filename);

                $template->ruta = $destinationPath.$filename;
                $template->save();
            }
            return redirect()->route('index.templates')->with('success', 'La plantilla se ha registrado exitosamente');
        } catch (Exception $e) {
            return redirect()->back()->with('warning', 'Ocurrió un error al hacer esta acción');
        }
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $template     = Template::find($id);

        $data = [
            'template'    =>  $template,
        ];
        return view('template.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $template = Template::find($id);
            $template->idPhase       = $request['fase'];
            $template->titulo  = $request['titulo'];
            if($request['obligatorio']==true)
                $template->obligatorio  = 1;
            else
                $template->obligatorio  = 2;
            $template->save();
            if(isset($request['ruta']) && $request['ruta'] != ""){
                if(file_exists($template->ruta)){
                    unlink($template->ruta);
                }
                $destinationPath = 'uploads/templates/'; // upload path
                $extension = $request['ruta']->getClientOriginalExtension();
                $filename = $template->id.'.'.$extension; 
                $request['ruta']->move($destinationPath, $filename);
                $template->ruta = $destinationPath.$filename;
                $template->save();
            }
            return redirect()->route('index.templates')->with('success', 'La plantilla se ha modificado exitosamente');
        } catch (Exception $e) {
            return redirect()->back()->with('warning', 'Ocurrió un error al hacer esta acción');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $template   = Template::find($id);
            
            //Restricciones

            $template->delete();

            return redirect()->route('index.templates')->with('success', 'La plantilla se ha eliminado exitosamente');
        } catch (Exception $e){
            return redirect()->back()->with('warning', 'Ocurrió un error al hacer esta acción');
        } 
    }
}
