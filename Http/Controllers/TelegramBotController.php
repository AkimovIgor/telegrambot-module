<?php

namespace Modules\TelegramBot\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use Modules\TelegramBot\Services\TelegramBotService;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramBotController extends Controller
{
    protected $telegramBotService;

    public function __construct(TelegramBotService $telegramBotService)
    {
        $this->telegramBotService = $telegramBotService;
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
//        dd($this->telegramBotService->getBotInfo());
        return view('telegrambot::index');
    }

    public function settings(Request $request)
    {
        $data = $request->except('_token');
        foreach ($data as $key => $value) {
            DotenvEditor::setKey('TELEGRAM_' . strtoupper($key), $value);
            DotenvEditor::save();
        }
        return redirect()->back()->with([
            'class' => 'success',
            'message' => 'Config saved.',
        ]);
    }

    public function webhook()
    {
        $updates = $this->telegramBotService->getUpdates();
//        $this->telegramBotService->handleUpdates($updates);
//        $this->telegramBotService->handleCommands();
    }

    public function getWebhookinfo(Request $request)
    {
        $result = json_decode($this->telegramBotService->getWebhookInfo());
        $hasCertificates = $result->has_custom_certificate ?: 'false';
        return redirect()->back()->with([
            'class' => 'info',
            'message' => "
                    <b>URL:</b> {$result->url}<br>
                    <b>Certificates:</b> {$hasCertificates}<br>
                    <b>Pending update count:</b> {$result->pending_update_count}<br>
                    <b>Max connections:</b> {$result->max_connections}<br>
                    <b>IP:</b> {$result->ip_address}
                "
        ]);
    }

    public function setWebhook(Request $request)
    {
        $params = $request->all();
        Validator::make($params, [
            'url' => Rule::requiredIf(function () use ($request) {
                return $request->action != 'set_webhook';
            }),
        ]);

        if ($certificate = config('telegram.bots.mybot.certificate_path')) {
            $params['certificate'] = storage_path($certificate);
        }

        $result = $this->telegramBotService->setWebhook($params);
        if ($result)
            return redirect()->back()->with([
                'class' => 'success',
                'message' => 'Webhook successfully attached.'
            ]);
        return redirect()->back()->with([
            'class' => 'danger',
            'message' => 'Failed to attach webhook, please check your url.'
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('telegrambot::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('telegrambot::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('telegrambot::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
