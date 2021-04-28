<?php

namespace Modules\TelegramBot\Http\Controllers;

use App\Role;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use Modules\TelegramBot\Entities\TelegramBot;
use Modules\TelegramBot\Services\TelegramBotService;

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
        $bot = TelegramBot::where('bot_token', config('telebot.bots.' . config('telebot.default') . '.token'))->first();
        $roles = Role::all();
        return view('telegrambot::index', compact('bot', 'roles'));
    }

    public function settings(Request $request)
    {
        $data = $request->except([
            '_token',
            'settings_start_message',
            'settings_roles',
        ]);
        foreach ($data as $key => $value) {
            DotenvEditor::setKey('TELEGRAM_' . strtoupper($key), $value);
            DotenvEditor::save();
        }

        if (!$botSetting = TelegramBot::where('bot_token', $data['bot_token'])->first()) {
            $botSetting = new TelegramBot();
        }
        $data['async_requests'] = $data['async_requests'] == 'true' ? 1 : 0;
        $data['bot_debug'] = $data['bot_debug'] == 'true' ? 1 : 0;
        $data['settings'] = [
            'start_message' => $request->settings_start_message,
            'roles' => $request->settings_roles
        ];
        $botSetting->fill($data);
        $botSetting->save();

        return redirect()->back()->with([
            'class' => 'success',
            'message' => 'Config saved.',
        ]);
    }

    public function webhook()
    {
        $this->telegramBotService->getUpdates();
    }

    public function getWebhookinfo(Request $request)
    {
        $result = json_decode($this->telegramBotService->getWebhookInfo());

        if (!$result && !isset($result->has_custom_certificate)) {
            return redirect()->back()->with([
                'class' => 'info',
                'message' => 'API token don\'t  set .'
            ]);
        }

        $hasCertificates = $result->has_custom_certificate ?: 'false';
        if ($result->url) {
            $message = "
                    <b>URL:</b> {$result->url}<br>
                    <b>Certificates:</b> {$hasCertificates}<br>
                    <b>Pending update count:</b> {$result->pending_update_count}<br>
                    <b>Max connections:</b> {$result->max_connections}<br>
                    <b>IP:</b> {$result->ip_address}
                ";
        } else {
            $message = 'Webhook don\'t  set.';
        }
        return redirect()->back()->with([
            'class' => 'info',
            'message' => $message
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

        if ($result) {
            DotenvEditor::setKey('TELEGRAM_WEBHOOK_URL', $params['url']);
            DotenvEditor::save();
            return redirect()->back()->with([
                'class' => 'success',
                'message' => 'Webhook successfully attached.'
            ]);
        }

        return redirect()->back()->with([
            'class' => 'danger',
            'message' => 'Failed to attach webhook, please check your url.'
        ]);
    }

    public function deleteWebhook()
    {
        $result = $this->telegramBotService->setWebhook(['url' => '']);

        if ($result) {
            DotenvEditor::setKey('TELEGRAM_WEBHOOK_URL', '');
            DotenvEditor::save();
            return redirect()->back()->with([
                'class' => 'success',
                'message' => 'Webhook was deleted.'
            ]);
        }

        return redirect()->back()->with([
            'class' => 'danger',
            'message' => 'Failed to delete webhook.'
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
