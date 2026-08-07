<?php

namespace App\Http\Controllers;

use App\Models\Commitment;
use App\Models\Deliverable;
use App\Models\Framework;
use App\Models\Gallery;
use App\Models\Kpi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthLoginController extends Controller
{
    //
//    use AuthenticatesUsers;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function index()
    {
        // return view('demo-expired');

        // Fetch the latest 3 active gallery items for the welcome page
        $galleries = Gallery::active()
            ->ordered()
            ->limit(3)
            ->get();

        // Count commitments, deliverables and KPIs for the active framework
        $commitmentsCount = 0;
        $deliverablesCount = 0;
        $kpisCount = 0;
        $activeFramework = Framework::where('status', 'Active')->first();

        if ($activeFramework) {
            $commitmentsCount = Commitment::where('framework_id', $activeFramework->id)->count();
            $deliverablesCount = Deliverable::where('framework_id', $activeFramework->id)->count();
            $kpisCount = Kpi::where('framework_id', $activeFramework->id)->count();
        }

        return view('welcome', compact('galleries', 'commitmentsCount', 'deliverablesCount', 'kpisCount'));
    }

    public function showLoginForm()
    {
        return view('pages.auth.login');
    }

    public function login(Request $request)
    {
//        return bcrypt(123456);
        $this->validateLogin($request);

        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        return $this->sendFailedLoginResponse($request);
    }

    protected function validateLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);
    }

    public function credentials(Request $request)
    {
        return ['email' => $request->email, 'password' => $request->password];
    }

    protected function attemptLogin(Request $request)
    {
        return Auth::attempt(
            $this->credentials($request), $request->filled('remember')
        );
    }

    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();

//        $this->clearLoginAttempts($request);

        return redirect()->intended($this->redirectPath());
    }

    protected function redirectPath()
    {
        $user = Auth::user();

        // Redirect Governor users to statistics page
        if ($user && $user->isGovernor()) {
            return route("dashboard.statistics");
        }

        // Facilitators: land on Sector Head → Facilitator review queue
        if ($user && $user->isFacilitator()) {
            return route("delivery.awaiting.verification");
        }

        return route("dashboard");
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        return redirect()->route('login')
            ->withInput($request->only('email', 'remember'))
            ->withErrors([
                'email' => __('auth.failed'),
            ]);
    }


    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
