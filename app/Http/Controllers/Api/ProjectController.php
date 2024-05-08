<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Commitment;
use App\Models\Deliverable;
use App\Models\Kpi;
use App\Models\Notification;
use App\Models\PerformanceTracking;
use App\Models\Sector;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    //

    public function index(Request $request, $id = null)
    {
        try {
            $where = [];
            if ($id) {
                $where[] = ['commitments.id', '=', $id];
            }
            $commts = Commitment::leftJoin('comments', 'commitments.id', '=', 'comments.commitment_id')
                ->select([
                    'commitments.id', 'commitments.name', 'comments.commitment_id',
                    'description', DB::raw("COUNT(comments.id) AS comments_count"), 'commitments.img_url'
                ])
                ->where($where)
                ->groupBy('commitments.id')
                ->get();
            $projects = [];
            foreach ($commts as $commt) {
                $comment = Comment::comment($commt->commitment_id);
                $projects[] = [
                    'id' => $commt->id, 'name' => $commt->name,
                    'description' => $commt->description, 'comments_count' => $commt->comments_count,
                    'photo' => $commt->img_url != null ? asset('uploads/' . $commt->img_url) : '',
                    'comments' => $comment == null ? [] : [$comment], 'deliverables' => []
                ];
            }

            return response()->json(['success' => true, 'message' => 'KPI list', 'data' => $projects]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
        }
    }

    public function project(Request $request, $id)
    {
        try {
            $commt = Commitment::leftJoin('comments', 'commitments.id', '=', 'comments.commitment_id')
                ->where('commitments.id', $id)
                ->select([
                    'commitments.id', 'commitments.name', 'comments.commitment_id',
                    'description', DB::raw("COUNT(comments.id) AS comments_count"), 'commitments.img_url'
                ])
                ->first();

            $deliverables = Deliverable::where('commitment_id', $commt->id)
                ->select([
                    'deliverables.id', 'deliverables.commitment_id', 'deliverables.budget',
                    'deliverables.start_date', 'deliverables.end_date', 'deliverables.deliverable'
                ])->get();
            $comments = Comment::where('commitment_id', $commt->id)
                ->select([
                    'id', 'comment', 'commenter_name AS name',
                    'commitment_id AS project_id',
                    DB::raw(" CASE
                WHEN TIMESTAMPDIFF(SECOND, created_at, NOW()) < 60 THEN
                    CONCAT(TIMESTAMPDIFF(SECOND, created_at, NOW()), ' seconds ago')
                WHEN TIMESTAMPDIFF(MINUTE, created_at, NOW()) < 60 THEN
                    CONCAT(TIMESTAMPDIFF(MINUTE, created_at, NOW()), ' minutes ago')
                WHEN TIMESTAMPDIFF(HOUR, created_at, NOW()) < 60 THEN
                    CONCAT(TIMESTAMPDIFF(HOUR, created_at, NOW()), ' hours ago')
                ELSE
                    CONCAT(TIMESTAMPDIFF(DAY, created_at, NOW()), ' days ago')
                END AS date")
                ])
                ->orderBy('comments.id', 'DESC')->get();


            $project = [
                'id' => $commt->id, 'name' => $commt->name, 'commitment_id' => $commt->commitment_id,
                'description' => $commt->description, 'comments_count' => $commt->comments_count,
                'photo' => $commt->img_url != null ? asset('uploads/' . $commt->img_url) : '',
                'comments' => $comments, 'deliverables' => $deliverables
            ];
            return response(['success' => true, 'message' => "", 'data' => $project]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
        }
    }

    public function addComment(Request $request)
    {
        try {
            $this->validate($request,
                [
                    'project_id' => "required",
                    'name' => "required",
                    'comment' => "required",
                ]);

            $project = Commitment::find($request->project_id);
            if ($project) {
                $comment = new Comment();
                $comment->commitment_id = $request->project_id;
                $comment->comment = $request->comment;
                $comment->commenter_name = $request->name;
                $comment->save();
                if ($comment) {
                    return response(['success' => true, 'message' => 'Comment added']);
                } else {
                    return response(['success' => false, 'message' => 'Failed to create comment']);
                }

            }
            return response(['message' => "Invalid Project", 'errors' => []]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
        }
    }

    public function sectors(Request $request)
    {

        try {
            $sectors = Sector::select(['id', 'sector_name', 'description'])->get();
            return response(['success' => true, 'message' => "", 'data' => $sectors]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
        }
    }

    public function getUser(Request $request)
    {
        try {
            $users = User::select(['users.id', 'full_name AS name', 'email', 'phone_number', 'image_url AS photo', 'token', 'role AS rank', 'entity_id'])
                ->leftJoin('user_roles', 'user_roles.user_id', '=', 'users.id')->get();
            $userEx = [];
            foreach ($users as $user) {
                if (in_array($user->rank, ['Sector Head', 'Sector Admin'])) {
                    $sector = Sector::find($user->entity_id);
                    $sName = $sector ? $sector->name : "";
                } elseif ($user->rank == 'Governor') {
                    $sName = "Jigawa State";
                } elseif ($user->rank == 'System Admin') {
                    $sName = "System";
                } else {
                    $sName = "Delivery Department";
                }
                $userEx[] = [
                    'id' => $user->id, 'name' => $user->name,
                    'email' => $user->email, 'phone' => $user->phone_number,
                    'photo' => asset('uploads/users/' . $user->photo), 'token' => $user->token,
                    'rank' => $user->rank, 'sector' => $sName
                ];
            }

            return response(['message' => 'User List', 'success' => true, 'data' => $userEx]);
        } catch (\Exception $e) {
            return response(['message' => $e->getMessage(), 'success' => false, 'data' => []]);
        }
    }

    public function editUser(Request $request)
    {
        try {
            $user = Auth::user();
            $this->validate($request, [
                'name' => 'required',
                'email' => 'required|email|unique:users,email',
                'phone' => 'required|unique:users,phone_number',
//            'sector'=>'required|exists:sectors,id'

            ]);
            $roles = ['Governor' => 'State', 'System Admin' => 'System', 'Sector Head' => 'Sector', 'Sector Admin' => 'Sector', 'Delivery Department' => 'Deliverable'];
//            $user = User::find($user->id);
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone_number = $request->phone;
            $userRole = $user->role();
            if (!$userRole) {
                $userRole = new UserRole();
                $userRole->user_id = $user->id;
                $userRole->role_status = 'Active';
            }
            $userRole->role = $user->rank;
            $userRole->target_entity = $roles[$request->role];
            $userRole->entity_id = $roles[$request->role] == 'Sector' ? $request->sector_id : 0;
            $userRole->save();
            if (in_array($user->rank, ['Sector Head', 'Sector Admin'])) {
                $sector = Sector::find($user->entity_id);
                $sName = $sector ? $sector->name : "";
            } elseif ($user->rank == 'Governor') {
                $sName = "Jigawa State";
            } elseif ($user->rank == 'System Admin') {
                $sName = "System";
            } else {
                $sName = "Delivery Department";
            }
            if ($user->save()) {
                $userX = [
                    'id' => $user->id, 'name' => $user->name,
                    'email' => $user->email, 'phone' => $user->phone_number,
                    'photo' => asset('uploads/users/' . $user->photo), 'token' => $user->token,
                    'rank' => $user->rank, 'sector' => $sName
                ];
                return response(['success' => true, 'message' => 'User record updated', 'data' => $userX]);
            } else {
                return response(['success' => false, 'message' => 'Failed to update user records', 'data' => []]);
            }
        } catch (\Exception $e) {
            return response(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
        }

    }

    public function changePassword(Request $request)
    {
        try {
            $user = Auth::user();
            $request->validate([
                'old' => 'required',
                'password' => 'required|min:8',
            ]);


            if (!Hash::check($request->old, $user->password)) {
                return response()->json(['message' => 'Incorrect old password', 'success' => false, 'data' => []], 401);
            }

            // Update user's password
            $user->password = Hash::make($request->password);
            $user->save();
            if (in_array($user->rank, ['Sector Head', 'Sector Admin'])) {
                $sector = Sector::find($user->entity_id);
                $sName = $sector ? $sector->name : "";
            } elseif ($user->rank == 'Governor') {
                $sName = "Jigawa State";
            } elseif ($user->rank == 'System Admin') {
                $sName = "System";
            } else {
                $sName = "Delivery Department";
            }
            $userX = [
                'id' => $user->id, 'name' => $user->name,
                'email' => $user->email, 'phone' => $user->phone_number,
                'photo' => asset('uploads/users/' . $user->photo), 'token' => $user->token,
                'rank' => $user->rank, 'sector' => $sName
            ];

            return response()->json(['success' => true, 'message' => 'Password changed successfully', 'data' => $userX], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => []], 200);
        }
    }

    public function report(Request $request)
    {
        try {
            $year = date('Y');
            $reportX = [];
            foreach (Sector::get() as $sector) {
                $commitmentIds = $sector->commitments->pluck('id');
                $deliverableIds = Deliverable::whereIn('commitment_id', $commitmentIds)->pluck('id');
                $kpiIds = Kpi::whereIn('deliverable_id', $deliverableIds)->pluck('id');
                $perf = PerformanceTracking::whereIn('kpi_id', $kpiIds)
                    ->select([
                        'quarter',
                        DB::raw("SUM( IF( delivery_department_value > 0 AND milestone > 0, (delivery_department_value / milestone) * 100, 0 )) /COUNT(delivery_department_value) AS performance",
                        )])->where('year', $year)->whereIn('quarter', [1, 2, 3, 4])->groupBy('quarter')->orderBy('quarter')->get();
                $reportX[$sector->sector_name] = [
                    'sector' => $sector->sector_name,
                    'first_quarter' => [
                        'img' => asset('dist/images/arrow-' . ($perf[0]->performance >= 50 ? 'up' : 'down') . '.png'),
                        'value' => number_format($perf[0]->performance, 1) . "%"
                    ],
                    'second_quarter' => [
                        'img' => asset('dist/images/arrow-' . ($perf[1]->performance >= 50 ? 'up' : 'down') . '.png'),
                        'value' => number_format($perf[1]->performance, 1) . "%"
                    ],
                    'third_quarter' => [
                        'img' => asset('dist/images/arrow-' . ($perf[2]->performance >= 50 ? 'up' : 'down') . '.png'),
                        'value' => number_format($perf[2]->performance, 1) . "%"
                    ],
                    'fourth_quarter' => [
                        'img' => asset('dist/images/arrow-' . ($perf[3]->performance >= 50 ? 'up' : 'down') . '.png'),
                        'value' => number_format($perf[3]->performance, 1) . "%"
                    ],
                ];

            }

            return response()->json(['success' => true, 'message' => 'fetched records', 'data' => $reportX]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'failed to fetch records.' . $e->getMessage(), 'data' => []]);
        }
    }

    public function insertCommitment(Request $request)
    {
        try {
            $this->validate($request, [
                'sector_id' => 'required',
                'name' => 'required',
                'description' => 'required',
                'start_date' => 'required',
                'duration_in_days' => 'required',
//            'budget'=>'required',
            ]);

            $commitment = new Commitment();
            $commitment->sector_id = $request->sector_id;
            $commitment->name = $request->name;
            $commitment->start_date = $request->start_date;
            $commitment->duration_in_days = $request->duration_in_days;
            $commitment->description = $request->description;
            $commitment->budget = $request->budget;
            if ($commitment->save()) {
                return response()->json(['success' => true, 'message' => 'Commitment created', 'data' => $commitment]);
            }
            return response()->json(['success' => false, 'message' => 'failed to create commitment', 'data' => []]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
        }
    }

    public function insertDeliverable(Request $request)
    {
        try {
            $this->validate($request, [
                'commitment_id' => 'required',
                'deliverable' => 'required',
//            'description'=>'required',
                'start_date' => 'required',
                'end_date' => 'required',
//            'budget'=>'required',
            ]);

            $deliverable = new Deliverable();
            $deliverable->commitment_id = $request->commitment_id;
            $deliverable->deliverable = $request->deliverable;
            $deliverable->start_date = $request->start_date;
            $deliverable->end_date = $request->end_date;
//        $deliverable->description = $request->description;
            $deliverable->budget = $request->budget;
            if ($deliverable->save()) {
                return response()->json(['success' => true, 'message' => 'Deliverable created', 'data' => $deliverable]);
            }
            return response()->json(['success' => false, 'message' => 'failed to create Deliverable', 'data' => []]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
        }
    }

    public function insertKpi(Request $request)
    {
        try {
            $this->validate($request, [
                'deliverable_id' => 'required',
                'kpi' => 'required',
                'target_value' => 'required',
                'start_date' => 'required',
                'end_date' => 'required',
                'unit_of_measurement' => 'required',
            ]);

            $kpi = new Kpi();
            $kpi->deliverable_id = $request->deliverable_id;
            $kpi->kpi = $request->kpi;
            $kpi->target_value = $request->target_value;
            $kpi->start_date = $request->start_date;
            $kpi->end_date = $request->end_date;
            $kpi->budget = $request->unit_of_measurement;
            if ($kpi->save()) {
                return response()->json(['success' => true, 'message' => 'KPI created', 'data' => $kpi]);
            }
            return response()->json(['success' => false, 'message' => 'failed to create KPI', 'data' => []]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
        }
    }

    public function getKPIs(Request $request, $id = null)
    {
        try {
            $where = [];
            if ($id) {
                $where[] = ['deliverable_id', '=', $id];
            }
            return response()->json(['success' => true, 'message' => 'KPI list', 'data' => Kpi::where($where)->get()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
        }
    }



    public function notifications(Request $request)
    {

        $user = Auth::user();
        $date = \Carbon\Carbon::now();
        $lastMonth = $date->subMonth(2)->format('Y-m-d');


        $notifications = Notification::join("users AS receiver", "receiver.id", "=", "notifications.user_id")
            ->leftJoin("users AS sender", "sender.id", "=", "notifications.sender_id")
            ->select([
                'notifications.id', 'user_id',
                'type', 'title', 'body', 'sender_id',
                'model_id', 'status', DB::raw("IF(sender.name,sender.name,'System') AS sender"),
                'receiver.name AS receiver', 'notifications.created_at AS posted_date'
            ])
            ->whereRaw("(notifications.created_at>='$lastMonth' OR status='Not Read') AND user_id=" . $user->id)
            ->orderBy('notifications.created_at','DESC');

        if ($request->id) {
            return $notifications->where('id', '=', $request->id)->first();
        }
        return $notifications->get();
    }

    public function savePushNotificationToken(Request $request)
    {
        try {
            auth()->user()->update(['fcm_token'=>$request->token]);
            return response(["message"=>'token saved successfully.']);
        }catch (\Exception $exception){
            return response(["message"=>'Something went wrong']);
        }
    }

}
