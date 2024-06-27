<?php

namespace App\Controllers;

use Core\Http\Request;
use Lib\FlashMessage;
use Core\Http\Controllers\Controller;

class AppointmentsController extends Controller
{
    public function index(Request $request): void
    {
        $paginator = $this->current_user->appointments()->paginate(page: $request->getParam('page', 1));
        $appointments = $paginator->registers();

        $title = 'Agendamentos Registrados';

        if ($request->acceptJson()) {
            $this->renderJson('appointments/list_appointments', compact('paginator', 'appointments', 'title'));
        } else {
            $this->render('appointments/list_appointments', compact('paginator', 'appointments', 'title'));
        }
    }

    public function new(): void
    {
        $appointment = $this->current_user->appointments()->new();

        $title = 'Novo Agendamento';
        $this->render('appointments/new_appointment', compact('appointment', 'title'));
    }

    public function create(Request $request): void
    {
        $params = $request->getParams();

        if (isset($params['appointment']['date'])) {
            $date = \DateTime::createFromFormat('d/m/Y', $params['appointment']['date']);
            if ($date) {
                $params['appointment']['date'] = $date->format('Y-m-d');
            } else {
                FlashMessage::danger("Data inválida.");
                $title = "Novo Agendamento";
                $appointment = $this->current_user->appointments()->new();
                $this->render("appointments/new_appointment", compact("appointment", "title"));
                return;
            }
        }

        $appointment = $this->current_user->appointments()->new($params['appointment']);

        if ($appointment->save()) {
            FlashMessage::success("Agendamento Salvo Com Sucesso");
            $this->redirectTo(route("list.appointaments"));
        } else {
            $title = "Novo Agendamento";
            $this->render("appointments/new_appointment", compact("appointment", "title"));
        }
    }


    public function show(Request $request): void
    {
        $id = $request->getParam("id");
        $appointment = $this->current_user->appointments()->findById((int)$id);

        if ($appointment !== null) {
            $title = "Agendamento: " . $appointment->id;
            $this->render("appointments/appointment_detail", compact("appointment", "title"));
        } else {
            $this->redirectTo(route("list.appointaments"));
        }
    }

    public function edit(Request $request): void
    {
        $id = $request->getParam("id");
        $params = $request->getParams();
        $appointment = $this->current_user->appointments()->findById((int)$id);

        $title = "Editar Agendamento #{$appointment->id}";
        $this->render('appointments/edit_appointment', compact('appointment', 'title'));
    }

    public function update(Request $request): void
    {
        $id = $request->getParam("id");
        $params = $request->getParam('appointment');

        $date = \DateTime::createFromFormat('d/m/Y', $params['new_date']);
        if (!$date) {
            FlashMessage::danger("Data inválida.");
            $this->renderEditForm("Editar Agendamento", $id, $params);
            return;
        }

        $params['date'] = $date->format('Y-m-d');
        $appointment = $this->current_user->appointments()->findById($id);

        $this->updateAppointment($appointment, $params);

        if ($appointment->save()) {
            FlashMessage::success("Agendamento Atualizado Com Sucesso");
            $this->redirectTo(route("list.appointaments"));
        } else {
            FlashMessage::danger("Erro ao atualizar agendamento.");
            $this->renderEditForm("Editar Agendamento", $id, $params, $appointment);
        }
    }
    /** 
     * @property Appointment $appointment
     * @param array $params
     */
    private function updateAppointment($appointment, $params): void
    {
        $appointment->psychologist_id = $params['psychologist_id'];
        $appointment->date = $params['date'];
        $appointment->start_time = $params['start_time'];
        $appointment->end_time = $params['end_time'];
        $appointment->client_id = $params['client_id'];
    }

    private function renderEditForm($title, $id, $params, $appointment = null): void
    {
        $this->render("appointments/edit_appointment", compact("title", "id", "params", "appointment"));
    }


    public function delete(Request $request): void
    {
        $params = $request->getParams();

        $appointment = $this->current_user->appointments()->findById($params['id']);
        $appointment->destroy();

        FlashMessage::success('Agendamento removido com sucesso!');
        $this->redirectTo(route('list.appointaments'));
    }
}
