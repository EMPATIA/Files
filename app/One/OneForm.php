<?php

namespace App\One;

use Form;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Session;
use ONE;

class OneForm {
    private $name = null;
    private $type = null;
    private $layout = null;
    private $title = null;
    private $body = '';
    private $form;

    public function __construct($name, $type, $layout, $title) {
        $this->name = $name;
        $this->type = $type;
        $this->layout = $layout;
        $this->title = $title;
    }

    public function show($id, $editAction, $deleteAction, $version = null) {
        if($this->type == 'show')
            $this->form['show'] = [
                'title' => trans($this->title.'.show'),
                'title_button' => ONE::actionButtons($id, ['edit' => $editAction, 'delete' => $deleteAction], $version),
            ];

        return $this;
    }

    public function edit($object, $updateAction, $cancelAction) {
        if($this->type == 'edit')
            $this->form['edit'] = [
                'title' => trans($this->title.'.edit'),
                'form' => Form::model($object, ['method' => 'PATCH', 'url' => action($updateAction, $object->id), 'name' => $this->name, 'id' => $this->name]),
                'form_close' => Form::close(),
                'submit' => Form::submit(trans('form.save'), ['class' => 'btn btn-flat btn-primary']),
                'cancel' => Form::button(trans('form.cancel'), ['class' => 'btn btn-flat btn-default', 'onclick' => "location.href='".action($cancelAction, $object->id)."'" ]),
            ];

        return $this;
    }

    public function create($createAction, $cancelAction) {
        if($this->type == 'create')
            $this->form['create'] = [
                'title' => trans($this->title.'.create'),
                'form' => Form::open(['action' => $createAction, 'name' => $this->name, 'id' => $this->name]),
                'form_close' => Form::close(),
                'submit' => Form::submit(trans('form.create'), ['class' => 'btn btn-flat btn-primary']),
                'cancel' => Form::button(trans('form.cancel'), ['class' => 'btn btn-flat btn-default', 'onclick' => "location.href='".action($cancelAction)."'" ]),
            ];

        return $this;
    }

    public function addField($name, $label, $input, $value, $noTop = false) {
        if($this->type == 'create' || $this->type == 'edit')
            $this->addFieldEdit($name, $label, $input);
        else
            $this->addFieldShow($label, $value, $noTop);

        return $this;
    }

    private function addFieldShow($label, $value, $noTop) {
        $html = "<dt>".$label."</dt>";
        $html .= "<dd> ".$value." </dd>";

        if ($this->body != "" && !$noTop) {
            $html = "<hr style='margin: 10px 0 10px 0'>" . $html;
        }

        $this->body .= $html;
    }

    private function addFieldEdit($name, $label, $input) {
        $e = "";

        if(Session::has('errors')) {
            $errors = Session::get('errors');
        }

        if(Session::has('errors') && $errors->has($name)) {
            $e = "has-error";
        }
        $html = '<div class="form-group '.$e.'">';
        $html .= Form::label($name, $label);
        $html .= $input;

        if(Session::has('errors') && $errors->has($name)) {
            $html .= '<p class="help-block">'.$errors->first($name).'</p>';
        }
        $html .= '</div>';

        $this->body .= $html;
    }

    public function startFormGroup($title){
        $html = "<div id='block-form-group' class='one-form-group'>";
        $html .= "<div id='title-form-group' class='one-form-group-title'>" . $title . "</div>";
        $html .= "<div id='body-form-group' class='one-form-group-body'>";

        $this->body .= $html;

        return $this;
    }

    public function endFormGroup() {
        $this->body .= "</div> </div>";

        return $this;
    }

    public function addHTML($input) {
        $this->body .= $input;

        return $this;
    }

    public function make() {
        if($this->type == 'show') $this->body = "<dl>".$this->body."</dl>";

        return view($this->layout, $this->form[$this->type])->with('body', $this->body);
    }
}