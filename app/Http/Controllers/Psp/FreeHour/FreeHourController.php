<?php namespace Intranet\Http\Controllers\Psp\FreeHour;

use Illuminate\Http\Request;
use Auth;
use Intranet\Http\Requests;
use Intranet\Models\FreeHour;
use Intranet\Models\Supervisor;
use Intranet\Models\Student;
use Intranet\Models\PspStudent;
use Intranet\Models\PspProcess;
use Intranet\Models\PspProcessxSupervisor;
use Intranet\Http\Controllers\Controller;
use Intranet\Http\Requests\FreeHourRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FreeHourController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $supervisor = Supervisor::where('iduser',Auth::User()->IdUsuario)->get()->first();

        $freeHours = DB::table('freehours')->join('pspprocessesxsupervisors','pspprocessesxsupervisors.idpspprocess','=','freehours.idpspprocess')->join('pspprocesses','pspprocessesxsupervisors.idpspprocess','=','pspprocesses.id')->join('Curso','pspprocesses.idcurso','=','Curso.IdCurso')->select('freehours.id', 'freehours.idpspprocess','freehours.fecha','freehours.hora_ini','Curso.Nombre')->where('pspprocessesxsupervisors.idsupervisor',$supervisor->id)->where('freehours.deleted_at',null)->orderBy('fecha','asc')->orderBy('hora_ini','asc')->paginate(10);

        foreach ($freeHours as $freeHour) {
            $dt = new Carbon($freeHour->fecha);        
            $freeHour->fecha = $dt->format('d-m-Y');
        }
        
        $data = [
            'freeHours'    =>  $freeHours,
        ];

        return view('psp.freeHour.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //Primero hay que analizar si el supervisor puede crear mas disponibilidades
        //El maximo de disponibilidades es A/S, donde A = cantidad de alumnos en psp y S = cantidad de supervisores

        $a = PspStudent::count();

        if($a == 0){
            return redirect()->route('freeHour.index')->with('warning','Para registrar una disponibilidad, ingrese previamente al sistema una lista de alumnos');
        }        

        $supervisor = Supervisor::where('iduser',Auth::User()->IdUsuario)->get()->first();

        //ver si hay procesos

        $procxs = PspProcessxSupervisor::where('idsupervisor',$supervisor->id)->get();
        $proc = array(); 
            $r = count($procxs);   
            if($r>0){
                foreach($procxs as $p){
                    $proc[]=PspProcess::find($p->idpspprocess);
                }
            }

        $cantDisp = FreeHour::where('idsupervisor',$supervisor->id)->count();

        $maxi = $this->maximum();
        
        $data =[
            'pspproc'    =>  $proc,
            ];  
        
        if($cantDisp < $maxi){
            return view('psp.freeHour.create',$data);    
        }else{
            return redirect()->route('freeHour.index')->with('warning','Ha llegado al maximo de disponibildades a registrar para eleccion del alumno. Maximo: '.$maxi);
        }
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(FreeHourRequest $request)
    {
        //
        try {       
            //Se verifica si ya existe un horario
            $dt = Carbon::createFromFormat('d-m-Y',$request['fecha']);     
            $previousFH = FreeHour::where([
                ['fecha',$dt->format('Y-m-d')],
                ['hora_ini',$request['hora_ini']],
                ['idpspprocess',$request['Proceso_de_Psp']],
                ])->get()->first();
            
            if($previousFH!=null){
                return redirect()->route('freeHour.create')->with('warning','Ya registro previamente una reunion con fecha: '.$dt->format('d-m-Y').' a las '.$request['hora_ini'].' horas.');
            }
            //Se registra el nuevo horario
            $supervisor = Supervisor::where('iduser',Auth::User()->IdUsuario)->get()->first();

            $freeHour = new FreeHour;                
            $freeHour->fecha = $dt;                
            $freeHour->hora_ini = $request['hora_ini'];
            $freeHour->cantidad = 1;
            $freeHour->idsupervisor = $supervisor->id;
            $freeHour->idpspprocess = $request['Proceso_de_Psp'];
            $freeHour->save();

            $f = FreeHour::where('idsupervisor',$supervisor->id)->count();
            $m = $this->maximum();

            return redirect()->route('freeHour.index')->with('success','Su disponibilidad se ha registrado exitosamente.');
            // Tiene registrado '.$f.'/'.$m.' disponibilidades para eleccion del alumno
        } catch (Exception $e) {
            return redirect()->back()->with('warning','Ocurrio un error al realizar la accion');
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
        $freeHour = FreeHour::find($id);
        $dt = new Carbon($freeHour->fecha);        
        $freeHour->fecha = $dt->format('d-m-Y');
        
        $data = [
            'freeHour' => $freeHour,
        ];

        return view('psp.freeHour.show',$data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $freeHour = FreeHour::find($id);

        $dt = new Carbon($freeHour->fecha);        
        $freeHour->fecha = $dt->format('d-m-Y');
        //dd($freeHour->fecha);

        $data = [
            'freeHour' => $freeHour,
        ];
        //dd($data);
        return view('psp.freeHour.edit',$data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(FreeHourRequest $request, $id)
    {
        //
        try {
            $dt = Carbon::createFromFormat('d-m-Y',$request['fecha']);
            $previousFH = FreeHour::where([
                ['fecha',$dt->format('Y-m-d')],
                ['hora_ini',$request['hora_ini']],
                ])->get()->first();

            if($previousFH!=null){
                return redirect()->route('freeHour.edit',$id)->with('warning','Ya registro previamente una reunion con fecha: '.$dt->format('d-m-Y').' a las '.$request['hora_ini'].' horas.');
                //
            }

            $freeHour = FreeHour::find($id);
            $freeHour->fecha = Carbon::createFromFormat('d-m-Y',$request['fecha']);
            $freeHour->hora_ini = $request['hora_ini'];
            $freeHour->save();

            return redirect()->route('freeHour.show',$id)->with('success','Su disponibilidad se ha actualizado exitosamente');

            
        } catch (Exception $e) {
            return redirect()->back()->with('warning','Ocurrio un error al realizar la accion');
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
        //
        try {
            $freeHour = FreeHour::find($id);
            $freeHour->delete();
            return redirect()->route('freeHour.index')->with('success','La disponibilidad se ha eliminado exitosamente');

        } catch (Exception $e) {
            return redirect()->back()->with('warning','Ocurrio un error al realizar la accion');
        }
    }

    private function maximum(){
        $a = PspStudent::count();
        $s = Supervisor::count();
        $maximum = $a/$s;

        return $maximum;
    }

}
