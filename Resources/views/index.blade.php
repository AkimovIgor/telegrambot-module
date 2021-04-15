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
                                        <input type="text" class="form-control" name="url" id="url" value="{{config('telebot.bots.akimigor_bot.webhook.url') }}">
                                    </div>
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
                                        <label for="bot_token">Telegram API token</label>
                                        <input type="text" class="form-control" name="bot_token" id="bot_token" value="{{ config('telebot.bots.akimigor_bot.token') }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="certificate_path">Path to SSL certificate</label>
                                        <input type="text" class="form-control" name="certificate_path" id="certificate_path" value="{{ config('telegram.bots.mybot.certificate_path') }}">
                                    </div>
                                    <div class="form-check pb-3">
                                        <input type="hidden" name="async_requests" value="false">
                                        <input type="checkbox" class="form-check-input" id="async_requests" @if(config('telebot.async')) checked @endif name="async_requests" value="true">
                                        <label class="form-check-label" for="async_requests">Enable async requests</label>
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
