<?php

namespace App\Http\Controllers\API;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function getAdmin()
    {
        try {
            $user = Auth::user();
            return response()->json(['status' => 200, 'data' => $user]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => "SomeThign Went Wrong Please try Again later"]);
        }
    }
    public function profile()
    {
        try {
            $user = Auth::user();
            return response()->json(['status' => 200, 'data' => $user]);
        } catch (\Throwable $th) {
            dd($th);
            //throw $th;
        }
    }
    
     public function AllUsersLists(Request $request)
    {
        $searchQuery = $request->input('query');
        $query = User::query();
    
        if ($searchQuery) {
            $query->where(function ($query) use ($searchQuery) {
                $query->where('name', 'LIKE', '%' . $searchQuery . '%')
                    ->orWhere('mobile', 'LIKE', '%' . $searchQuery . '%');
            });
        }

        // Add order by id in descending order
        $users = $query->orderBy('id', 'DESC')->get();
    
        return response()->json(['status' => 200, 'data' => $users]);
    }
    
      public function user_Details(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 403, 'error' => $validator->errors()], 403);
            }

            $validated = $validator->validate();
            $id = $validated['id'];

            // Retrieve user details
            $user_details = User::where('id', $id)->first();

            if (!$user_details) {
                return response()->json(['status' => 404, 'message' => 'User not found'], 404);
            }

            return response()->json(['status' => 200, 'data' => $user_details]);
        } catch (\Throwable $th) {
            // Return error response
            return response()->json(['status' => 500, 'error' => $th->getMessage()], 500);
        }
    }

    public function Update_users(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:users,id',
                'name' => 'nullable|string|max:255',
                'mobile' => 'nullable|string|max:20',
                'password' => 'nullable|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 403, 'error' => $validator->errors()], 403);
            }

            $validated = $validator->validate();
            $id = $validated['id'];

            // Find the user
            $user = User::find($id);

            if (!$user) {
                return response()->json(['status' => 404, 'message' => 'User not found'], 404);
            }

            // Update user details
            if (isset($validated['name'])) {
                $user->name = $validated['name'];
            }
            if (isset($validated['mobile'])) {
                $user->mobile = $validated['mobile'];
            }
            if (isset($validated['password'])) {
                $user->password = bcrypt($validated['password']);
            }

            $user->save();

            return response()->json(['status' => 200, 'message' => 'User updated successfully', 'data' => $user]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()], 500);
        }
    }


    public function user_delete(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 403, 'error' => $validator->errors()], 403);
            }

            $validated = $validator->validate();
            $id = $validated['id'];

            // Find the user and delete
            $user = User::find($id);

            if (!$user) {
                return response()->json(['status' => 404, 'message' => 'User not found'], 404);
            }

            $user->delete();

            return response()->json(['status' => 200, 'message' => 'User deleted successfully']);
        } catch (\Throwable $th) {
            // Return error response
            return response()->json(['status' => 500, 'error' => $th->getMessage()], 500);
        }
}
}