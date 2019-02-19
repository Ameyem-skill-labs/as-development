<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use App\Traits\ActivationTrait;
use App\Traits\CaptchaTrait;
use App\Traits\CaptureIpTrait;
// use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Foundation\Auth\RedirectsUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use jeremykenedy\LaravelRoles\Models\Role;
// use App\Http\Controllers\Auth\Request;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;

use App\batch;
use App\Branch;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
     */

    use ActivationTrait;
    use CaptchaTrait;
    // use RegistersUsers;
    use RedirectsUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */


    protected $redirectTo = '/activate';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', [
            'except' => 'logout',
        ]);
    }
    public function showRegistrationForm()

    {
        // return "registration form";
        return view('auth.register');
    }
    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
        {
            // $data['captcha'] = $this->captchaCheck();
// return $data;
            // if (!config('settings.reCaptchStatus')) {
                $data['captcha'] = true;
            // }
        if($data['role_id']==6){
            return Validator::make($data,
                [
                    'role_id' => 'required',
                    'institutes_id' => 'required',
                    'branch_id' => 'required',
                    'batch_id' => 'required',

                    'first_name'            => 'required|max:255',
                    // 'last_name'             => 'max:255',
                    'email'                 => 'required|email|max:255|unique:users',
                    'password'              => 'required|min:6|max:30|confirmed',
                    'password_confirmation' => 'required|same:password',
                    // 'g-recaptcha-response'  => '',
                    // 'captcha'               => 'required|min:1',
                ],
                [
                    // 'name.unique'                   => trans('auth.userNameTaken'),
                    // 'name.required'                 => trans('auth.userNameRequired'),
                    'first_name.required'           => trans('auth.fNameRequired'),
                    // 'last_name.required'            => trans('auth.lNameRequired'),
                    'email.required'                => trans('auth.emailRequired'),
                    'email.email'                   => trans('auth.emailInvalid'),
                    'password.required'             => trans('auth.passwordRequired'),
                    'password.min'                  => trans('auth.PasswordMin'),
                    'password.max'                  => trans('auth.PasswordMax'),
                    // 'g-recaptcha-response.required' => trans('auth.captchaRequire'),
                    // 'captcha.min'                   => trans('auth.CaptchaWrong'),
                ]
            );
        }else{
            return Validator::make($data,
                [
                    'role_id' => 'required',
                    'institutes_id' => 'required',
                    
                    // 'name'                  => 'required|max:255|unique:users',
                    'first_name'            => 'required|max:255',
                    // 'last_name'             => 'max:255',
                    'email'                 => 'required|email|max:255|unique:users',
                    'password'              => 'required|min:6|max:30|confirmed',
                    'password_confirmation' => 'required|same:password',
                    'phone_number' => 'required',
                    // 'g-recaptcha-response'  => '',
                    // 'captcha'               => 'required|min:1',
                ],
                [
                    // 'name.unique'                   => trans('auth.userNameTaken'),
                    // 'name.required'                 => trans('auth.userNameRequired'),
                    'first_name.required'           => trans('auth.fNameRequired'),
                    // 'last_name.required'            => trans('auth.lNameRequired'),
                    'email.required'                => trans('auth.emailRequired'),
                    'email.email'                   => trans('auth.emailInvalid'),
                    'password.required'             => trans('auth.passwordRequired'),
                    'password.min'                  => trans('auth.PasswordMin'),
                    'password.max'                  => trans('auth.PasswordMax'),
                    // 'g-recaptcha-response.required' => trans('auth.captchaRequire'),
                    // 'captcha.min'                   => trans('auth.CaptchaWrong'),
                ]
            );
        }

        
        // return $validator;

        
    }
    public function processRequestData(Request $request){
// return $request;
        if(isset($request->batch_name_in)){
            
            batch::firstOrCreate(['name'=>$request->batch_name_in]);
            $batch = batch::where('name',$request->batch_name_in)->first();
            $request->merge(['batch_id'=>$batch->id]);
            
        }
       
        if(isset($request->branch_name_in)){
            // $validator= new         Validator;
            $data['branch_name']=$request->branch_name_in;
            $localreq= new Request;
            

            // $validatedData = $request->validate([
            //     'title' => 'required|unique:posts|max:255',
            //     'body' => 'required',
            // ]);

            $validator= Validator::make($data,
            ['branch_name'=> 'required|max:255'],
            ['branch_name.required' => 'branch_name required']);
            if ($validator -> fails()) 
                { 
                  return  back() -> withInput($request->all()) -> withErrors($validator); 
                }
            Branch::firstOrCreate(['name'=>$request->branch_name_in]);
            $branch = Branch::where('name',$request->branch_name_in)->first();
            $request->merge(['branch_id'=>$branch->id]);
        }
        // return [$branch->id,$batch->id];
        return $request;
    }

    public function register(Request $request)
    {
        // return
      $request=
      $this->processRequestData($request);
    //    var_dump( $request);
    //    return $request;
// return $request->except(['branch_name_in','batch_name_in']);
  
        $validator= new         Validator;
        
        $validator = $this->validator($request->except(['branch_name_in','batch_name_in','_token'])); 

                if ($validator -> fails()) 
                { 
                    // return "validator fail";
                  return  back() -> withInput($request->all()) -> withErrors($validator); 
                }
        // }
        // return $validator;
        event(new Registered($user = $this->create($request->except(['branch_name_in','batch_name_in','_token']))));
 
        $this->guard()->login($user);
 
        return $this->registered($request, $user)
                        ?: redirect($this->redirectPath());
    }

    

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     *
     * @return User
     */
    protected function create(array $data)
    {
        $ipAddress = new CaptureIpTrait();
        if($data['role_id']!=6){
            $data['branch_id']=0;
            $data['batch_id']=0;
            //for tutapp
            $data['type'] = 'mentor';
        }else{
            $data['type'] = 'student';
        }
        $data['status'] =0;
        // $role = Role::where('slug', '=', 'unverified')->first();

        $user = User::create([

                'role_id' => $data['role_id'],
                'institutes_id' =>  $data['institutes_id'],
                'branch_id' => $data['branch_id'],
                'batch_id' => $data['batch_id'],
                'name'              => strtolower($data['email']),
                'first_name'        => $data['first_name'],
                'last_name'         => $data['last_name'],
                'email'             => strtolower($data['email']),
                'password'          => Hash::make($data['password']),
                'token'             => str_random(64),
                'signup_ip_address' => $ipAddress->getClientIp(),
                'activated'         => 1,
                'phone_number' => $data['phone_number'],
                'type'      =>$data['type'] ,
                'status'   =>$data['status'] ,
            ]);

        // $user->attachRole($role);s
        $this->initiateEmailActivation($user);

        return $user;
    }
    protected function guard()
    {
        return Auth::guard();
    }
    protected function registered(Request $request, $user)
    {
        //
    }
}
