<?php 

namespace App\Services;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserServices{

    
    public function createUser($data){
        
        
        $data['password'] = Hash::make($data['password']);
        
        //username udih before @ , example : zaki@gmail.com ->username=zaki
        $data['username'] = strstr($data['email'], '@', true);

       return User::create($data);
        
    }
}