@extends('telegrambot::layouts.master')

@section('content')
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <nav class="pb-3">
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <a class="nav-item nav-link active" id="nav-dashboard-tab" data-toggle="tab" href="#nav-dashboard" role="tab" aria-controls="nav-dashboard" aria-selected="true">Dashboard</a>
                        <a class="nav-item nav-link" id="nav-settings-tab" data-toggle="tab" href="#nav-settings" role="tab" aria-controls="nav-settings" aria-selected="false">Settings</a>
                    </div>
                </nav>

                @if(session()->has('message'))
                    <div class="alert alert-{{ session('class') }} alert-dismissible fade show" role="alert">
                        {!! session('message') !!}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6">

                        <div class="tab-content" id="nav-tabContent">
                            <div class="tab-pane fade show active" id="nav-dashboard" role="tabpanel" aria-labelledby="nav-dashboard-tab">

                                <form class="webhook-info" action="{{ route('telegrambot.get_webhook_info') }}" method="POST">
                                    @csrf
                                </form>
                                <form class="webhook-set" action="{{ route('telegrambot.set_webhook') }}" method="POST">
                                    <div class="form-group">
                                        <label for="url">Webhook URI</label>
                                        <input type="text" class="form-control" name="url" id="url" value="{{config('telebot.bots.' . config('telebot.default') . '.webhook.url') }}">
                                    </div>
                                    @csrf
                                </form>
                                <form class="webhook-delete" action="{{ route('telegrambot.delete_webhook') }}" method="POST">
                                    @method('DELETE')
                                    @csrf
                                </form>

                                <div class="form-group">
                                    <label for="action">Action</label>
                                    <select class="form-control select-action" id="action" name="action">
                                        <option value="info">Get information</option>
                                        <option value="set">Set webhook</option>
                                        <option value="delete">Delete webhook</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <button class="btn btn-primary" onclick="let val = $('.select-action').val(); $('.webhook-' + val).submit() ">Send</button>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="nav-settings" role="tabpanel" aria-labelledby="nav-settings-tab">
                                <form action="{{ route('telegrambot.settings') }}" method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <label for="bot_name">Telegram bot name</label>
                                        <input type="text" class="form-control" name="bot_name" id="bot_name" value="{{ config('telebot.default') }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="bot_token">Telegram bot API token</label>
                                        <input type="text" class="form-control" name="bot_token" id="bot_token" value="{{ config('telebot.bots.' . config('telebot.default') . '.token') }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="bot_cert_path">Path to SSL certificate</label>
                                        <input type="text" class="form-control" name="bot_cert_path" id="bot_cert_path" value="{{ config('telebot.bots.' . config('telebot.default') . '.webhook.certificate') }}">
                                    </div>
                                    <div class="form-check pb-3">
                                        <input type="hidden" name="async_requests" value="false">
                                        <input type="checkbox" class="form-check-input" id="async_requests" @if(config('telebot.bots.' . config('telebot.default') . '.async')) checked @endif name="async_requests" value="true">
                                        <label class="form-check-label" for="async_requests">Enable async requests</label>
                                    </div>
                                    <div class="form-check pb-3">
                                        <input type="hidden" name="bot_debug" value="false">
                                        <input type="checkbox" class="form-check-input" id="bot_debug" @if(config('telebot.bots.' . config('telebot.default') . '.exceptions')) checked @endif name="bot_debug" value="true">
                                        <label class="form-check-label" for="bot_debug">Debug mode</label>
                                    </div>

                                    <h5>Additional settings</h5>

                                    <div class="form-group">
                                        <label for="settings_start_message">Start message text</label>
                                        <textarea type="text" class="form-control" name="settings_start_message" id="settings_start_message">@if($bot && $bot->settings && $bot->settings->start_message){{ $bot->settings->start_message }}@endif</textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="settings_roles">Allowed roles</label><br>
                                        <select multiple="multiple" class="" id="settings_roles" name="settings_roles[]">
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}" @if($bot && $bot->settings && $bot->settings->roles && in_array((string)$role->id, $bot->settings->roles)) selected @endif>{{ $role->display_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <button class="btn btn-primary" type="submit">Save</button>
                                    </div>

                                </form>

                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">

                    </div>
                </div>

            </div>
        </div>
@endsection
