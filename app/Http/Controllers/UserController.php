<?php

namespace App\Http\Controllers;

use App\Models\Customers;
use App\Models\CustomersOccupation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Throwable;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $customers = Customers::with('user')->get();
        foreach ($customers as $customer) {
            $customer->occupation = $this->takeOccupation($customer->id);
        }

        return response()->json($customers);
    }

    public function takeOccupation($id)
    {
        $customer = CustomersOccupation::with('occupation')->where('customerId', $id)->first();
        // return is_null($customer->occupation) ? $customer->occupation : 'Médico';
        return 'Médico'; 
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $id = Uuid::uuid4();
            DB::beginTransaction();
            $customer = new Customers();
            $user = new User();
            $customerOccupation = new CustomersOccupation();

            $customer->insert([
                'updatedAt' => Carbon::now(),
                'createdAt' => Carbon::now(),
                'name' => $request->name,
                'email' => $request->email,
                'id' => $id,
            ]);

            $customerOccupation->insert([
                'updatedAt'  => Carbon::now(),
                'createdAt'  => Carbon::now(),
                'customerId' => $id,
                'occupationId' => $this->chooseOccupation($request->occupation)
            ]);

            $user->insert([
                'id' => Uuid::uuid4(),
                'name' => $request->name,
                'email' => $request->email,
                'updatedAt' => Carbon::now(),
                'createdAt' => Carbon::now(),
                'password' => $this->generateHash($request->password),
                'domain' => $this->role($request->manager),
                'domainId' =>  $id,
                'firstAccess' => Carbon::now(),
                'lastAccess' => Carbon::now()
            ]);

            DB::commit();
            return response()->json(['success' => true, 'resposta' => 'usuario criado com sucesso', 'usuario' => $user]);
        } catch (Throwable $e) {
            Log::error($e);
            DB::rollBack();
            return response()->json(['success' => false, 'resposta' => 'usuario não foi criado']);
        }
    }

    public function chooseOccupation($occupation): string
    {
        switch ($occupation) {
            case 'Especialista':
                return '676044b4-e5be-4727-af0c-d5f4fab89bf0';
                break;
            case 'Gestor de Escala (não sou médico)':
                return '87981cbe-fb3c-40fc-8da5-d3fc17bcbfe9';
                break;
            case 'Médico':
                return 'b9c74f84-f8dc-42b9-9a39-a8edaa7f5e41';
                break;
            case 'Residente':
                return 'c811f3ed-4f6a-4ecb-ba09-6f785eef7951';
                break;
            case 'Estudante':
                return 'f270a37d-1c8c-407e-accb-e02b4edc5fa3';
                break;
            default:
                return 'b9c74f84-f8dc-42b9-9a39-a8edaa7f5e41';
                break;
        }
    }

    public function role($manager)
    {
        if ($manager == true) {
            return 'customer-manager';
        } else {
            return 'customer';
        }
    }

    public function generateHash($secret)
    {

        $data = http_build_query(
            array(
                'password' => $secret, 'cost' => 10
            )
        );

        $opts = array(
            'http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $data
            )
        );

        $context  = stream_context_create($opts);

        $r = file_get_contents('https://www.toptal.com/developers/bcrypt/api/generate-hash.json', false, $context);

        $r = json_decode($r, true);

        return $r['hash'];
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $customer = Customers::where('id', $id)->first();
        $customer->name = $request->name;
        $customer->email = $request->email;

        $customerOccupation = CustomersOccupation::where('customerId', $id)->first();
        $customerOccupation->crm = $request->crm ? $request->crm : $customerOccupation->crm;
        $customerOccupation->updatedAt = Carbon::now();
        $customerOccupation->occupationId = $this->chooseOccupation($request->occupation);

        $user = User::where('domainId', $id)->first();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->lastAccess = Carbon::now();
        $user->domain = $this->role($request->manager);
        $user->password = $this->generateHash($request->password);


        $user->save();
        $customer->save();
        $customerOccupation->save();

        return response()->json(['sucess' => true, 'resposta' => 'Usuario atualizado com sucesso']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Customers::where('id', $id)->delete();
        User::where('domainId', $id)->delete();
        CustomersOccupation::where('customerId', $id)->delete();

        return response()->json(['sucess' => true, 'resposta' => 'usuario deletado']);
    }
}
