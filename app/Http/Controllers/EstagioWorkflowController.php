<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\EstagioRequest;

use App\Estagio;
use Illuminate\Support\Facades\Gate;
use App\User;
use Uspdev\Replicado\Pessoa;
use Auth;
use App\Mail\enviar_para_analise_tecnica_mail;
use App\Mail\EstagioStatusChangeMail;
use Illuminate\Support\Facades\Mail;

class EstagioWorkflowController extends Controller
{

    #Funções Análise Técnica

    public function enviar_para_analise_tecnica(EstagioRequest $request, Estagio $estagio){

        if ( Gate::allows('empresa',$estagio->cnpj) | Gate::allows('admin') ) {
            $validated = $request->validated();
            $estagio->update($validated);

            if($request->enviar_para_analise_tecnica=="enviar_para_analise_tecnica"){
                $estagio->last_status = 'em_elaboracao';
                $estagio->status = 'em_analise_tecnica';
                #$workflow = $estagio->workflow_get();
                #$workflow->apply($estagio,'enviar_para_analise_tecnica');
                $estagio->save();

                // Envio de email
                Mail::send(new enviar_para_analise_tecnica_mail($estagio));
            }
        } else {
            request()->session()->flash('alert-danger', 'Sem permissão para executar ação');
        }

        return redirect("/estagios/{$estagio->id}");
    }

    public function analise_tecnica(Request $request, Estagio $estagio){

        if (Gate::allows('admin')) {

            $estagio->analise_tecnica = $request->analise_tecnica;
            $estagio->analise_tecnica_user_id = Auth::user()->numero_usp;
            $estagio->save();

            if($request->analise_tecnica_action == 'concluir') {
                if(is_null($estagio->analise_academica)){
                    request()->session()->flash('alert-danger','Não existe parecer de mérito para esse estágio. Não é possível concluir.');
                    return redirect("/estagios/{$estagio->id}");
                }
                $estagio->last_status = $estagio->status;
                $estagio->status = 'concluido';
                $estagio->save();
                Mail::send(new EstagioStatusChangeMail($estagio));
                return redirect("/estagios/{$estagio->id}");
            }

            if($request->analise_tecnica_action == 'indeferimento_analise_tecnica'){
                $request->validate([
                    'analise_tecnica' => 'required',
                ]);
                $estagio->last_status = $estagio->status;
                $workflow = $estagio->workflow_get();
                $workflow->apply($estagio,$request->analise_tecnica_action);
                Mail::send(new EstagioStatusChangeMail($estagio));
                $estagio->save();
                return redirect("/estagios/{$estagio->id}");
            } else {
                if($estagio->numparecerista){
                    $estagio->last_status = $estagio->status;
                    $workflow = $estagio->workflow_get();
                    $workflow->apply($estagio,$request->analise_tecnica_action);
                    $estagio->save();
                    Mail::send(new EstagioStatusChangeMail($estagio));
                } else {
                    request()->session()->flash('alert-danger','Não enviado para parecer de mérito! Informe o parecerista!');
                }
            }

        } else {
            request()->session()->flash('alert-danger', 'Sem permissão para executar ação');
        }
        return redirect("/estagios/{$estagio->id}");
    }

    public function mover_analise_tecnica(Request $request, Estagio $estagio){

        if (Gate::allows('admin')) {
            $estagio->last_status = $estagio->status;
            $estagio->status = 'em_analise_tecnica';
            $estagio->save();
            Mail::send(new EstagioStatusChangeMail($estagio));
        } else {
            request()->session()->flash('alert-danger', 'Sem permissão para executar ação');
        }

        return redirect("/estagios/{$estagio->id}");
    }

    #Funções Análise Acadêmica

    public function analise_academica(Request $request, Estagio $estagio){

        if (Gate::allows('parecerista')) {

            $request->validate([
                'atividadespertinentes' => 'required',
                'desempenhoacademico' => 'required',
                'horariocompativel' => 'required',
                'mediaponderada' => 'required',
                'atividadesjustificativa'=> 'required',
                'analise_academica'=> 'required',
                'tipodeferimento'=> 'required',
                'condicaodeferimento'=> 'required_if:tipodeferimento,==,Deferido com Restrição'
            ]);
            $estagio->analise_academica = $request->analise_academica;
            $estagio->mediaponderada = $request->mediaponderada;
            $estagio->horariocompativel = $request->horariocompativel;
            $estagio->desempenhoacademico = $request->desempenhoacademico;
            $estagio->atividadespertinentes = $request->atividadespertinentes;
            $estagio->atividadesjustificativa = $request->atividadesjustificativa;
            $estagio->tipodeferimento = $request->tipodeferimento;
            $estagio->condicaodeferimento = $request->condicaodeferimento;
            $estagio->analise_academica_user_id = Auth::user()->id;
            $estagio->numparecerista = User::find($estagio->analise_academica_user_id)->codpes;
            // Vamos sempre devolver para o setor de graduação depois do parecer
            $estagio->last_status = $estagio->status;
            $estagio->status = 'em_analise_tecnica';
            $estagio->save();
            Mail::send(new EstagioStatusChangeMail($estagio));   
            request()->session()->flash('alert-info','Parecer incluído com sucesso! Estágio enviado para o setor de graduação');    
        } else {
            request()->session()->flash('alert-danger', 'Sem permissão para executar ação');
        }
        return redirect("/estagios/{$estagio->id}");
    }

    public function editar_analise_academica(Request $request, Estagio $estagio){

        if (Gate::allows('parecerista')) {
            $estagio->last_status = $estagio->status;
            $estagio->status = 'em_analise_academica';
            $estagio->save();
        } else {
            request()->session()->flash('alert-danger', 'Sem permissão para executar ação');
        }
        return redirect("/estagios/{$estagio->id}");
    }   

    #Funções Concluido

    public function renovacao(Request $request, Estagio $estagio) {

        if ( Gate::allows('empresa',$estagio->cnpj) | Gate::allows('admin')) {

            $renovacao = $estagio->replicate();
            $renovacao->push();

            if(empty($estagio->renovacao_parent_id)){
                $renovacao->renovacao_parent_id = $estagio->id;
            }

            $request->validate([
                'renovacao_justificativa' => 'required',
            ]);
            $renovacao->renovacao_justificativa = $request->renovacao_justificativa;

            /* Verificar quais campos mais dever ser zerado na renovanção */
            $renovacao->analise_tecnica = null;
            $renovacao->analise_academica = null;
            $renovacao->analise_alteracao = null;
            $renovacao->tipodeferimento = null;
            $renovacao->condicaodeferimento = null;
            $renovacao->atividades = null;
            $renovacao->justificativa = null;
            $renovacao->atividadesjustificativa = null;
            $renovacao->save();

            $workflow = $renovacao->workflow_get();
            $workflow->apply($renovacao,'renovacao');
            $renovacao->save();
        } else {
            request()->session()->flash('alert-danger', 'Sem permissão para executar ação');
        }
        return redirect("estagios/{$renovacao->id}");
    }

    public function rescisao(Request $request, Estagio $estagio){

        if ( Gate::allows('empresa',$estagio->cnpj) | Gate::allows('admin')) {

            $request->validate([
                'rescisao_motivo' => 'required',
                'rescisao_data' => 'required|data',
            ]);
            $estagio->rescisao_motivo = $request->rescisao_motivo;
            $estagio->rescisao_data = implode('-',array_reverse(explode('/',$request->rescisao_data)));
            $estagio->last_status = $estagio->status;
            $estagio->save();
            $workflow = $estagio->workflow_get();
            $workflow->apply($estagio,'rescisao_do_estagio');
            $estagio->save();
            Mail::send(new EstagioStatusChangeMail($estagio));
        } else {
            request()->session()->flash('alert-danger', 'Sem permissão para executar ação');
        }
        return redirect("/estagios/{$estagio->id}");
    }

    public function iniciar_alteracao(Estagio $estagio) {

        if (Gate::allows('empresa',$estagio->cnpj)) {
            $estagio->last_status = $estagio->status;
            $workflow = $estagio->workflow_get();
            $workflow->apply($estagio,'iniciar_alteracao');
            $estagio->save();
            Mail::send(new EstagioStatusChangeMail($estagio));
        } else {
            request()->session()->flash('alert-danger', 'Sem permissão para executar ação');
        }
        return redirect("estagios/{$estagio->id}");

    }

    #Funções Alteração

    public function enviar_alteracao(EstagioRequest $request, Estagio $estagio){

        if (Gate::allows('empresa',$estagio->cnpj)) {
            $validated = $request->validated();
            $estagio->update($validated);
            $estagio->alteracao = $request->alteracao;
            $estagio->save();

            if($request->enviar_analise_tecnica_alteracao == 'enviar_analise_tecnica_alteracao'){
                $estagio->alteracao = $request->alteracao;
                $estagio->last_status = $estagio->status;
                $estagio->status = 'em_analise_tecnica';
                request()->session()->flash('alert-info', 'Enviado para análise do setor de graduação');
                $estagio->save();
                Mail::send(new EstagioStatusChangeMail($estagio));
            }
        } else {
            request()->session()->flash('alert-danger', 'Sem permissão para executar ação');
        }
        return redirect("/estagios/{$estagio->id}");
    }
}
