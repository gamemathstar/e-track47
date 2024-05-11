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

            $deliverables = [];
            foreach (Deliverable::where('commitment_id', $commt->id)->get() as $del) {
                $deliverables[] = [
                    'id' => $del->id, 'commitment_id' => $del->commitment_id, 'budget' => '',
                    'start_date' => date_format(date_create($del->start_date), "d M, Y"),
                    'end_date' => date_format(date_create($del->end_date), "d M, Y"),
                    'deliverable' => $del->deliverable
                ];
            }
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

    public function sectors(Request $request)
    {
        try {
            $user = Auth::user();

            if ($user->isGovernor() || $user->isSystemAdmin() || $user->isDeliveryDepartment())
                $sectors = Sector::select(['id', 'sector_name', 'description'])->get();
            else if ($sector = $user->isSectorHead())
                $sectors = Sector::select(['id', 'sector_name', 'description'])
                    ->where('id', $sector->id)
                    ->get();
            else
                $sectors = [];

            return response(['success' => true, 'message' => "", 'data' => $sectors]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
        }
    }

    public function commitments($sector_id)
    {
        try {
            $sector = Sector::find($sector_id);
            $commitments = $sector->__commitments()->get();

            $projects = [];
            foreach ($commitments as $commitment) {
                $date = date_create($commitment->start_date);
                $projects[] = [
                    'id' => $commitment->id, 'sector_id' => $commitment->sector_id,
                    'name' => $commitment->name, 'description' => $commitment->description,
                    'budget' => '₦' . number_format($commitment->budget, 2),
                    'start_date' => date_format($date, "d M, Y"),
                    'duration_in_days' => $commitment->duration_in_days,
                ];
            }

            return response()->json(['success' => true, 'message' => 'Commitments list', 'data' => $projects]);
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

    public function deliverables($commitment_id)
    {
        try {
            $deliverables = Deliverable::where(['commitment_id' => $commitment_id])->get();
            $data = [];
            foreach ($deliverables as $deliverable) {
                $data[] = [
                    'id' => $deliverable->id,
                    'commitment_id' => $deliverable->commitment_id,
                    'deliverable' => $deliverable->deliverable,
                    'budget' => '₦' . number_format($deliverable->budget, 2),
                    'start_date' => date_format(date_create($deliverable->start_date), "d M, Y"),
                    'end_date' => date_format(date_create($deliverable->end_date), "d M, Y"),
                ];
            }

            return response()->json(['success' => true, 'message' => 'Deliverables list', 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
        }
    }

    public function getUser(Request $request)
    {
        try {
            $users = User::select(['users.id', 'full_name AS name', 'email', 'phone_number', 'image_url AS photo', 'token', 'user_roles.role AS rank', 'entity_id'])
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
                'email' => 'required',
                'phone' => 'required',
//            'sector'=>'required|exists:sectors,id'

            ]);
            $roles = ['Governor' => 'State', 'System Admin' => 'System', 'Sector Head' => 'Sector', 'Sector Admin' => 'Sector', 'Delivery Department' => 'Deliverable'];
//            $user = User::find($user->id);
            $user->full_name = $request->name;
            $user->email = $request->email;
            $user->phone_number = $request->phone;
//            $userRole = $user->role();
//            if (!$userRole) {
//                $userRole = new UserRole();
//                $userRole->user_id = $user->id;
//                $userRole->role_status = 'Active';
//            }
//            $userRole->role = $user->rank;
//            $userRole->target_entity = $roles[$request->role];
//            $userRole->entity_id = $roles[$request->role] == 'Sector' ? $request->sector_id : 0;
//            $userRole->save();
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
//                $userX = [
//                    'id' => $user->id, 'name' => $user->name,
//                    'email' => $user->email, 'phone' => $user->phone_number,
//                    'photo' => asset('uploads/users/' . $user->photo), 'token' => $user->token,
//                    'rank' => $user->rank, 'sector' => $sName
//                ];
                return response(['success' => true, 'message' => 'Record updated']);
            } else {
                return response(['success' => false, 'message' => 'Failed to update record']);
            }
        } catch (\Exception $e) {
            return response(['success' => false, 'message' => $e->getMessage()]);
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
                $reportX[] = [
                    'sector' => $sector->sector_name,
                    'first' => isset($perf[0]) ? floatval(number_format($perf[0]->performance, 1)) : 0,
//                    [
//                        'img' => asset('dist/images/arrow-' . ($perf[0]->performance >= 50 ? 'up' : 'down') . '.png'),
//                        'value' => isset($perf[0]) ? number_format($perf[0]->performance, 1) . "%" : ""
//                    ],
                    'second' => isset($perf[1]) ? floatval(number_format($perf[1]->performance, 1)) : 0,
//                    [
//                        'img' => isset($perf[1]) ? asset('dist/images/arrow-' . ($perf[1]->performance >= 50 ? 'up' : 'down') . '.png') : '',
//                        'value' => isset($perf[1]) ? number_format($perf[1]->performance, 1) . "%" : ""
//                    ],
                    'third' => isset($perf[2]) ? floatval(number_format($perf[2]->performance, 1)) : 0,
//                    [
//                        'img' => isset($perf[2]) ? asset('dist/images/arrow-' . ($perf[2]->performance >= 50 ? 'up' : 'down') . '.png') : '',
//                        'value' => isset($perf[2]) ? number_format($perf[2]->performance, 1) . "%" : ""
//                    ],
                    'fourth' => isset($perf[3]) ? floatval(number_format($perf[3]->performance, 1)) : 0,
//                    [
//                        'img' => isset($perf[3]) ? asset('dist/images/arrow-' . ($perf[3]->performance >= 50 ? 'up' : 'down') . '.png') : '',
//                        'value' => isset($perf[3]) ? number_format($perf[3]->performance, 1) . "%" : ""
//                    ],
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
                'type' => "required",
                'description' => 'required',
                'start_date' => 'required',
                'end_date' => 'required',
                'status' => 'required',
                'budget' => 'required',
            ]);

            $dt_start = new \DateTime($request->start_date);
            $dt_end = new \DateTime($request->end_date);
            $diff = $dt_start->diff($dt_end);
            $duration = $diff->format('%a');

            $commitment = new Commitment();
            $commitment->sector_id = $request->sector_id;
            $commitment->name = $request->name;
            $commitment->type = $request->type;
            $commitment->start_date = $request->start_date;
            $commitment->end_date = $request->end_date;
            $commitment->duration_in_days = $duration;
            $commitment->description = $request->description;
            $commitment->status = $request->status;
            $commitment->budget = $request->budget;
            if ($commitment->save()) {
                return response()->json(['success' => true, 'message' => 'Commitment created']);
            }
            return response()->json(['success' => false, 'message' => 'failed to create commitment']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
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
                'budget' => 'required',
            ]);

            $deliverable = new Deliverable();
            $deliverable->commitment_id = $request->commitment_id;
            $deliverable->deliverable = $request->deliverable;
            $deliverable->start_date = $request->start_date;
            $deliverable->end_date = $request->end_date;
//        $deliverable->description = $request->description;
            $deliverable->budget = $request->budget;
            if ($deliverable->save()) {
                return response()->json(['success' => true, 'message' => 'Deliverable created']);
            }
            return response()->json(['success' => false, 'message' => 'failed to create Deliverable']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
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
            $kpis = Kpi::where($where)->get();
            $data = [];
            foreach ($kpis as $kpi) {
                $data[] = [
                    'id' => $kpi->id,
                    'deliverable_id' => $kpi->deliverable_id,
                    'kpi' => $kpi->kpi,
                    'target_value' => $kpi->target_value,
                    'start_date' => date_format(date_create($kpi->start_date), "d M, Y"),
                    'end_date' => date_format(date_create($kpi->end_date), "d M, Y"),
                    'unit_of_measurement' => $kpi->unit_of_measurement
                ];
            }

            return response()->json(['success' => true, 'message' => 'KPI list', 'data' => $data]);
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
            ->orderBy('notifications.created_at', 'DESC');

        if ($request->id) {
            return $notifications->where('id', '=', $request->id)->first();
        }
        return $notifications->get();
    }

    public function savePushNotificationToken(Request $request)
    {
        try {
            auth()->user()->update(['fcm_token' => $request->token]);
            return response(["message" => 'token saved successfully.']);
        } catch (\Exception $exception) {
            return response(["message" => 'Something went wrong']);
        }
    }

}
