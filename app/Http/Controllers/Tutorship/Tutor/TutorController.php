<?php

namespace Intranet\Http\Controllers\Tutorship\Tutor;

use Auth;
use Mail;
use Illuminate\Http\Request;
use Intranet\Http\Requests;
use Illuminate\Support\Facades\DB;
use Intranet\Http\Controllers\Controller;
use Intranet\Models\Teacher;
use Intranet\Models\TutSchedule;
use Intranet\Models\Tutorship;
use Intranet\Models\Tutstudent;
use Intranet\Models\Reason;
use Intranet\Models\TutMeeting;
use Illuminate\Support\Facades\Session; //<---------------------------------necesario para usar session

class TutorController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        $filters = $request->all();
        $specialty = Session::get('faculty-code');

        $tutors = Teacher::getTutorsFiltered($filters, $specialty);
        $tutors = $tutors->appends($filters);
        $horas = [];
        $alumnos = [];
        foreach ($tutors as $t) {
            $tutSchedule = TutSchedule::where('id_docente', $t->IdDocente)->get();
            $horas[$t->IdDocente] = $tutSchedule->count();

            $tutorship = Tutorship::where('id_tutor', $t->IdDocente)->get();
            $alumnos[$t->IdDocente] = $tutorship->count();
        }

        $data = [
            'tutors' => $tutors,
            'horas' => $horas,
            'alumnos' => $alumnos,
        ];

        return view('tutorship.tutor.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request) {
        $filters = $request->all();

        $specialty = Session::get('faculty-code');
        $teachers = Teacher::getCoordsFiltered($filters, $specialty);
        $data = [
            'teachers' => $teachers->appends($filters),
        ];

        return view('tutorship.tutor.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        if ($request['check'] != null) {
            foreach ($request['check'] as $idTeacher => $value) {
                try {
                    //se cambia el rol del profesor a TUTOR
                    DB::table('Docente')->where('IdDocente', $idTeacher)->update(['rolTutoria' => 1]);
                } catch (Exception $e) {
                    return redirect()->back()->with('warning', 'Ocurrió un error al hacer esta acción');
                }
            }
            return redirect()->route('tutor.index')->with('success', 'Se guardaron los tutores exitosamente');
        } else {
            return redirect()->route('tutor.index');
        }
        //VUELVE A la lista de tutores
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        $tutor = Teacher::find($id);
        $tutSchedule = TutSchedule::where('id_docente', $tutor->IdDocente)->get();
        $horas = $tutSchedule->count();

        $data = [
            'tutor' => $tutor,
            'horas' => $horas,
        ];

        return view('tutorship.tutor.show', $data);
    }

    public function activate($id) {
        try {
            $tutorships = Tutorship::where('id_profesor', $id)->get();

            foreach ($tutorships as $t) {
                $tutorshipTrash = Tutorship::find($t->id);

                $tutorshipNew = new Tutorship;
                $tutorshipNew->id_tutor = $tutorshipTrash->id_profesor;
                $tutorshipNew->id_profesor = $tutorshipTrash->id_profesor;
                $tutorshipNew->id_suplente = $tutorshipTrash->id_suplente;
                $tutorshipNew->id_alumno = $tutorshipTrash->id_alumno;
                $tutorshipNew->save();

                $student = Tutstudent::find($tutorshipTrash->id_alumno);
                $mail = $student->correo;
                $nuevoT = Teacher::find($tutorshipTrash->id_profesor);
                $antiguoT = Teacher::find($tutorshipTrash->id_tutor);

                $data = [
                    'nombreAlumno' => $student->nombre . ' ' . $student->ape_paterno . ' ' . $student->pae_materno,
                    'nuevoTutor' => $nuevoT->Nombre . ' ' . $nuevoT->ApellidoPaterno . ' ' . $nuevoT->ApellidoMaterno,
                    'antiguoTutor' => $antiguoT->Nombre . ' ' . $antiguoT->ApellidoPaterno . ' ' . $antiguoT->ApellidoMaterno,
                ];

                Mail::send('emails.notifyTutorActivate', $data, function($m) use($mail) {
                    $m->subject('[TUTORÍA] Cambio de tutor');
                    $m->to($mail);
                });

                $tutorshipTrash->delete();
            }

            DB::table('Docente')->where('IdDocente', $id)->update(['rolTutoria' => 1]);
            return redirect()->route('tutor.index')->with('success', 'Se activó al tutor y se reasignaron los alumnos del tutor suplente al tutor original, en caso hubiera.');
        } catch (Exception $e) {
            return redirect()->route('tutor.index')->with('warning', 'No se puede hacer la reasignación.');
        }
    }

    public function reassign($id) {
        $tutor = Teacher::find($id);
        $razones = Reason::where('tipo', 2)->get();
        $idEspecialidad = Session::get('faculty-code');
        $students = Tutorship::where('id_tutor', $id)
                    ->get();
        $tutors = Teacher::where('IdEspecialidad', $idEspecialidad)->where('rolTutoria', 1)->where('IdDocente', '!=', $id)->get();

        $horas = [];
        foreach ($tutors as $t) {
            $tutSchedule = TutSchedule::where('id_docente', $t->IdDocente)->get();
            $horas[$t->IdDocente] = $tutSchedule->count();
        }

        $quantityStudents   = $students->count();
        $quantityTutors     = $tutors->count();

        $allQuantity        = Tutstudent::getQuantityPerTutor($quantityTutors, $quantityStudents);


        $data = [
            'tutor' => $tutor,
            'razones' => $razones,
            'tutors' => $tutors,
            'horas' => $horas,
            'quantities'    => $allQuantity,   
        ];

        return view('tutorship.tutor.reassign', $data);
    }

    public function deactivate(Request $request, $id) {
        if ($request['motivo'] == "") {
            return redirect()->back()->with('warning', 'Se debe seleccionar un motivo por el cual se está reasignando los alumnos del tutor.');
        }
        if ($request['cant'] != null && $request['total'] != 0) {
            $sum = 0;
            foreach ($request['cant'] as $idTeacher => $value) {
                $sum = $sum + $value;
            }
            if ($sum != $request['total']) {
                return redirect()->back()->with('warning', 'Se cuenta con ' . $request['total'] . ' alumnos para reasignar, pero se está asignando ' . $sum . ' alumnos a los tutores suplentes.');
            } else {
                try {
                    //cambiar tutor principal y ponerles tutor suplente
                    foreach ($request['cant'] as $idTeacher => $cantAlumnos) {
                        $tutorships = Tutorship::where('id_tutor', $id)->take($cantAlumnos)->get();
                        foreach ($tutorships as $t) {
                            $tutorshipTrash = Tutorship::find($t->id);

                            $tutorshipNew = new Tutorship;
                            $tutorshipNew->id_tutor = $idTeacher;
                            $tutorshipNew->id_profesor = $tutorshipTrash->id_profesor;
                            $tutorshipNew->id_suplente = $idTeacher;
                            $tutorshipNew->id_alumno = $tutorshipTrash->id_alumno;
                            $tutorshipNew->save();

                            $student = Tutstudent::find($tutorshipTrash->id_alumno);
                            $mail = $student->correo;
                            $nuevoT = Teacher::find($idTeacher);
                            $antiguoT = Teacher::find($tutorshipTrash->id_profesor);

                            $data = [
                                'nombreAlumno' => $student->nombre . ' ' . $student->ape_paterno . ' ' . $student->pae_materno,
                                'nuevoTutor' => $nuevoT->Nombre . ' ' . $nuevoT->ApellidoPaterno . ' ' . $nuevoT->ApellidoMaterno,
                                'antiguoTutor' => $antiguoT->Nombre . ' ' . $antiguoT->ApellidoPaterno . ' ' . $antiguoT->ApellidoMaterno,
                            ];

                            Mail::send('emails.notifyTutorDeactivate', $data, function($m) use($mail) {
                                $m->subject('[TUTORÍA] Cambio de tutor');
                                $m->to($mail);
                            });

                            $tutorshipTrash->delete();

                            $citas = TutMeeting::where('id_tutstudent', $t->id_alumno)->where('id_docente', $t->id_tutor)->where('estado', 'Confirmada')->get();
                            if ($citas->count() != 0) {
                                foreach ($citas as $c) {
                                    $cita = TutMeeting::find($c->id);
                                    $cita->estado = 3;
                                    $cita->id_reason = $request['motivo'];
                                    $cita->save();
                                }
                            }
                        }
                    }
                    DB::table('Docente')->where('IdDocente', $id)->update(['rolTutoria' => 3]);
                    return redirect()->route('tutor.index')->with('success', 'Se reasignaron tutores suplentes a: (' . $request['total'] . ') alumnos.');
                } catch (Exception $e) {
                    return redirect()->back()->with('warning', 'Ocurrió un error al hacer esta acción');
                }
            }
        } else {
            return redirect()->route('tutor.index')->with('warning', 'No se puede hacer la reasignación.');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        try {
            DB::table('Docente')->where('IdDocente', $id)->update(['rolTutoria' => 3]);
            return redirect()->route('tutor.index')->with('success', 'Se desactivó al tutor exitosamente');
        } catch (Exception $e) {
            return redirect()->back()->with('warning', 'Ocurrió un error al hacer esta acción');
        }
    }

    // muestra el perfil del tutor que accede a sus datos
    public function myprofile() {
        $tutor = Auth::user()->professor;
        // dd($tutor);
        $data = [
            'tutor' => $tutor,
        ];

        return view('tutorship.tutor.myprofile', $data);
    }

}
