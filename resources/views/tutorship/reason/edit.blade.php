@extends('app')
@section('content')

<div class="row">
	<div class="col-md-12">
		<div class="page-title">
			<div class="title_left">
				<h3>Edición de Motivo</h3>
			</div>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-md-12 col-sm-12 col-xs-12">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">Información</h3>
			</div>
			<div class="panel-body">

				{{Form::open(['route' => ['motivo.update', $reason->id], 'class'=>'form-horizontal', 'id'=>'formSuggestion'])}}

				<div class="form-group">
					{{Form::label('Tipo *',null,['class'=>'control-label col-md-4 col-sm-3 col-xs-12'])}}
					<div class="col-md-4">
						{{Form::select('tipo', [1=>'De cancelación/rechazo de cita',2=>'De desactivación de tutor'], $reason->tipo, ['class' => 'form-control', 'required'])}}
					</div>
				</div>

				<div class="form-group">
					{{Form::label('Nombre *',null,['class'=>'control-label col-md-4 col-sm-3 col-xs-12'])}}
					<div class="col-md-4">
						{{Form::text('nombre',$reason->nombre,['class'=>'form-control', 'required', 'maxlength' => 50])}}
					</div>
				</div>		    		

				<div class="row">
					<div class="col-md-8 col-sm-12 col-xs-12">
						{{Form::submit('Guardar', ['class'=>'btn btn-success pull-right'])}}
						<a class="btn btn-default pull-right" href="{{ route('motivo.index',$reason->id) }}">Cancelar</a>
					</div>
				</div>
				{{Form::close()}}

			</div>
		</div>
	</div>
</div>

<!-- <script src="{{ URL::asset('js/myvalidations/investigation.js')}}"></script> -->

@endsection