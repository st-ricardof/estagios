<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use ZeroDaHero\LaravelWorkflow\Traits\WorkflowTrait;

class Estagio extends Model
{
    use WorkflowTrait;

    protected $guarded = ['id'];

    public function especifiquevtOptions(){
        return [
            'Mensal',
            'Diário'
        ];
    }

    public function tipobolsaOptions(){
        return [
            'Mensal',
            'Por Hora'
        ];
    }

    public function atividadespertinentesOptions(){
        return [
            'Sim',
            'Não',
            'Parcialmente'
        ];
    }
    
    public function buscastatusOptions(){
        return [
            'Sim',
            'Não',
            'Parcialmente'
        ];
    }  

    public function pandemiahomeofficeOptions(){
        return [
            'Sim',
            'Não'
        ];
    }


    public function tipodeferimentoOptions(){
        return [
            'Deferido',
            'Deferido com Restrição',
            'Indeferido'
        ];
    }

    public function condicaodeferimentoOptions(){
        return [
            'Sim',
            'Não'
        ];
    }
    
    public function getDataInicialAttribute($value) {
        return implode('/',array_reverse(explode('-',$value)));
    }

    public function setDataInicialAttribute($value) {
       $this->attributes['data_inicial'] = implode('-',array_reverse(explode('/',$value)));
    }

    public function getDataFinalAttribute($value) {
        return implode('/',array_reverse(explode('-',$value)));
    }

    public function setDataFinalAttribute($value) {
       $this->attributes['data_final'] = implode('-',array_reverse(explode('/',$value)));
    }

    public function getDataRescisaoAttribute($value) {
        return implode('/',array_reverse(explode('-',$value)));
    }

    public function setDataRescisaoAttribute($value) {
       $this->attributes['rescisao_data'] = implode('-',array_reverse(explode('/',$value)));
    }

    public function setCnpjAttribute($value){
        $this->attributes['cnpj'] = preg_replace("/[^0-9]/", "", $value);  
    }

    public function getStatus(){
        $status = [
            'em_elaboracao' => [
                'name' => "Em Elaboração",
                'optional' => 'Empresa'
            ],
            'em_analise_tecnica' => [
                'name' => "Análise Técnica",
                'optional' => 'Setor de Graduação'
            ],
            'em_analise_academica' => [
                'name' => "Parecer de Mérito",
                'optional' => 'Docente'
            ],
            'concluido' => [
                'name' => "Concluído",
                'optional' => 'Docente'
            ],
            'em_alteracao' => [
                'name' => "Aditivo de Alterações",
                'optional' => 'Empresa'
            ],
            'rescisao' => [
                'name' => "Rescisão",
                'optional' => 'Empresa'
            ],
        ];
        return $status;
    }

    public function analise_academica_user()
    {
        return $this->belongsTo('App\User');
    }

    public function analise_tecnica_user()
    {
        return $this->belongsTo('App\User');
    }

    public function analise_alteracao_user()
    {
        return $this->belongsTo('App\User');
    }
}
