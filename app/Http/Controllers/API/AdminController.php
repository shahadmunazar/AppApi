<?php

namespace App\Http\Controllers\API;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

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

            // Find the user and soft-delete
            $user = User::find($id);

            if (!$user) {
                return response()->json(['status' => 404, 'message' => 'User not found or already deleted'], 404);
            }

            // Perform Soft Delete. All user data (transactions, games, withdrawals) is safely preserved.
            $user->delete();

            return response()->json([
                'status' => 200, 
                'message' => 'User deleted successfully. All transaction history and user records are preserved.'
            ]);
        } catch (\Throwable $th) {
            // Return error response
            return response()->json(['status' => 500, 'error' => $th->getMessage()], 500);
        }
    }

    /**
     * Get audit logs showing who has done what, with Yajra DataTables support and default fetch all.
     */
    public function audit_logs(Request $request)
    {
        try {
            $query = \OwenIt\Auditing\Models\Audit::with(['user' => function ($q) {
                $q->withTrashed();
            }]);

            if ($request->has('event') && !empty($request->input('event'))) {
                $query->where('event', $request->input('event'));
            }

            if ($request->has('user_id') && !empty($request->input('user_id'))) {
                $query->where('user_id', $request->input('user_id'));
            }

            if ($request->has('model') && !empty($request->input('model'))) {
                $query->where('auditable_type', 'like', '%' . $request->input('model') . '%');
            }

            // Yajra DataTables format support
            if ($request->has('draw') || $request->input('datatable') == '1' || $request->input('format') === 'datatables') {
                return DataTables::of($query)
                    ->addColumn('user_name', function ($item) {
                        return $item->user ? $item->user->name : 'System/Admin';
                    })
                    ->addColumn('user_mobile', function ($item) {
                        return $item->user ? $item->user->mobile : 'N/A';
                    })
                    ->addColumn('model', function ($item) {
                        return class_basename($item->auditable_type);
                    })
                    ->editColumn('created_at', function ($item) {
                        return \Carbon\Carbon::parse($item->created_at)->setTimezone('Asia/Kolkata')->toDateTimeString();
                    })
                    ->rawColumns(['old_values', 'new_values'])
                    ->make(true);
            }

            $perPage = $request->input('per_page');
            $fetchAll = $request->has('all') || empty($perPage) || $perPage === 'all';

            if ($fetchAll) {
                // By default fetch all audit records
                $logs = $query->orderBy('id', 'desc')->get();
                $formatted = $logs->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'user_id' => $item->user_id,
                        'user_name' => $item->user ? $item->user->name : 'System/Admin',
                        'user_mobile' => $item->user ? $item->user->mobile : 'N/A',
                        'event' => $item->event,
                        'model' => class_basename($item->auditable_type),
                        'auditable_id' => $item->auditable_id,
                        'old_values' => $item->old_values,
                        'new_values' => $item->new_values,
                        'url' => $item->url,
                        'ip_address' => $item->ip_address,
                        'user_agent' => $item->user_agent,
                        'created_at' => \Carbon\Carbon::parse($item->created_at)->setTimezone('Asia/Kolkata')->toDateTimeString(),
                    ];
                });

                return response()->json([
                    'status' => 'success',
                    'data' => $formatted,
                    'total' => $logs->count(),
                    'pagination' => [
                        'current_page' => 1,
                        'total_pages' => 1,
                        'total_items' => $logs->count(),
                        'per_page' => $logs->count(),
                    ],
                ], 200);
            } else {
                $logs = $query->orderBy('id', 'desc')->paginate((int)$perPage);
                $formatted = collect($logs->items())->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'user_id' => $item->user_id,
                        'user_name' => $item->user ? $item->user->name : 'System/Admin',
                        'user_mobile' => $item->user ? $item->user->mobile : 'N/A',
                        'event' => $item->event,
                        'model' => class_basename($item->auditable_type),
                        'auditable_id' => $item->auditable_id,
                        'old_values' => $item->old_values,
                        'new_values' => $item->new_values,
                        'url' => $item->url,
                        'ip_address' => $item->ip_address,
                        'user_agent' => $item->user_agent,
                        'created_at' => \Carbon\Carbon::parse($item->created_at)->setTimezone('Asia/Kolkata')->toDateTimeString(),
                    ];
                });

                return response()->json([
                    'status' => 'success',
                    'data' => $formatted,
                    'pagination' => [
                        'current_page' => $logs->currentPage(),
                        'total_pages' => $logs->lastPage(),
                        'total_items' => $logs->total(),
                        'per_page' => $logs->perPage(),
                    ],
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch audit logs.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get soft-deleted records across Users, Played Games, Admin Added Money, Requests, and Withdrawals
     */
    public function deleted_records(Request $request)
    {
        try {
            $type = $request->input('type', 'users');
            $search = $request->input('search');
            $perPage = $request->input('per_page', 15);

            $counts = [
                'users' => User::onlyTrashed()->count(),
                'games' => \App\Models\PlayGame::onlyTrashed()->count(),
                'admin_money' => \App\Models\AdminAddedMoney::onlyTrashed()->count(),
                'add_money_requests' => \App\Models\AddMoneyRequest::onlyTrashed()->count(),
                'withdrawals' => \App\Models\WithdrawalMoney::onlyTrashed()->count(),
            ];

            $data = [];
            $pagination = null;

            if ($type === 'users') {
                $query = User::onlyTrashed()->withCount(['transactions', 'playGames', 'withdrawals']);
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%")
                          ->orWhere('mobile', 'LIKE', "%{$search}%")
                          ->orWhere('email', 'LIKE', "%{$search}%");
                    });
                }
                $paginator = $query->orderBy('deleted_at', 'desc')->paginate($perPage);
                $pagination = [
                    'current_page' => $paginator->currentPage(),
                    'total_pages' => $paginator->lastPage(),
                    'total_items' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                ];
                $data = $paginator->items();
            } elseif ($type === 'games') {
                $query = \App\Models\PlayGame::onlyTrashed()->with([
                    'user' => function ($q) { $q->withTrashed(); },
                    'category'
                ]);
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('Playing_Name', 'LIKE', "%{$search}%")
                          ->orWhere('play_game_id', 'LIKE', "%{$search}%")
                          ->orWhereHas('user', function ($uq) use ($search) {
                              $uq->where('name', 'LIKE', "%{$search}%")
                                 ->orWhere('mobile', 'LIKE', "%{$search}%");
                          });
                    });
                }
                $paginator = $query->orderBy('deleted_at', 'desc')->paginate($perPage);
                $pagination = [
                    'current_page' => $paginator->currentPage(),
                    'total_pages' => $paginator->lastPage(),
                    'total_items' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                ];
                $data = $paginator->items();
            } elseif ($type === 'admin_money') {
                $query = \App\Models\AdminAddedMoney::onlyTrashed()->with([
                    'user' => function ($q) { $q->withTrashed(); },
                    'admin' => function ($q) { $q->withTrashed(); }
                ]);
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('description', 'LIKE', "%{$search}%")
                          ->orWhere('amount', 'LIKE', "%{$search}%")
                          ->orWhereHas('user', function ($uq) use ($search) {
                              $uq->where('name', 'LIKE', "%{$search}%")
                                 ->orWhere('mobile', 'LIKE', "%{$search}%");
                          });
                    });
                }
                $paginator = $query->orderBy('deleted_at', 'desc')->paginate($perPage);
                $pagination = [
                    'current_page' => $paginator->currentPage(),
                    'total_pages' => $paginator->lastPage(),
                    'total_items' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                ];
                $data = $paginator->items();
            } elseif ($type === 'add_money_requests') {
                $query = \App\Models\AddMoneyRequest::onlyTrashed()->with([
                    'user' => function ($q) { $q->withTrashed(); }
                ]);
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('amount', 'LIKE', "%{$search}%")
                          ->orWhereHas('user', function ($uq) use ($search) {
                              $uq->where('name', 'LIKE', "%{$search}%")
                                 ->orWhere('mobile', 'LIKE', "%{$search}%");
                          });
                    });
                }
                $paginator = $query->orderBy('deleted_at', 'desc')->paginate($perPage);
                $pagination = [
                    'current_page' => $paginator->currentPage(),
                    'total_pages' => $paginator->lastPage(),
                    'total_items' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                ];
                $data = $paginator->items();
            } elseif ($type === 'withdrawals') {
                $query = \App\Models\WithdrawalMoney::onlyTrashed()->with([
                    'user' => function ($q) { $q->withTrashed(); }
                ]);
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('amount', 'LIKE', "%{$search}%")
                          ->orWhereHas('user', function ($uq) use ($search) {
                              $uq->where('name', 'LIKE', "%{$search}%")
                                 ->orWhere('mobile', 'LIKE', "%{$search}%");
                          });
                    });
                }
                $paginator = $query->orderBy('deleted_at', 'desc')->paginate($perPage);
                $pagination = [
                    'current_page' => $paginator->currentPage(),
                    'total_pages' => $paginator->lastPage(),
                    'total_items' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                ];
                $data = $paginator->items();
            }

            return response()->json([
                'status' => 'success',
                'counts' => $counts,
                'data' => $data,
                'pagination' => $pagination,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch deleted records.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a soft-deleted record
     */
    public function restore_record(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:users,user,games,game,admin_money,add_money_requests,add_money_request,withdrawals,withdrawal',
                'id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 422, 'error' => $validator->errors()], 422);
            }

            $type = $request->input('type');
            $id = $request->input('id');

            $model = null;
            if (in_array($type, ['user', 'users'])) {
                $model = User::onlyTrashed()->find($id);
            } elseif (in_array($type, ['game', 'games'])) {
                $model = \App\Models\PlayGame::onlyTrashed()->find($id);
            } elseif ($type === 'admin_money') {
                $model = \App\Models\AdminAddedMoney::onlyTrashed()->find($id);
            } elseif (in_array($type, ['add_money_request', 'add_money_requests'])) {
                $model = \App\Models\AddMoneyRequest::onlyTrashed()->find($id);
            } elseif (in_array($type, ['withdrawal', 'withdrawals'])) {
                $model = \App\Models\WithdrawalMoney::onlyTrashed()->find($id);
            }

            if (!$model) {
                return response()->json(['status' => 404, 'message' => 'Record not found in deleted records.'], 404);
            }

            $model->restore();

            return response()->json([
                'status' => 200,
                'message' => 'Record successfully restored.',
                'data' => $model
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to restore record.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}