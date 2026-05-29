<?php
namespace App\Http\Controllers;
use App\Models\Space;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\space_iq;
use App\Models\Conversation;
use App\Models\SpaceIqDocs;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Appointment;
use App\Models\Space_whapichannel_details;
use App\Models\Space_whapipayment_details;
use App\Models\BusinessCategory;
class SpaceController extends Controller
{
public function show(Request $request)
{
    $user = Auth::user();
    $client_id = $user->id;
    $spaces = Space::where('client_id', $client_id)
                ->select('id', 'name')
                ->get();
    return response()->json([
        'status'  => true,
        'spaces'  => $spaces,
        'message' => 'Spaces List'
    ]);
}


public function store(Request $request)
{
    DB::beginTransaction();
    try {
        $client = Auth::user();
        $client_id = $client->id;
        $url = 'https://n8n.brenops.com/webhook/0f24996b-b60c-4292-853c-a2923ba8a241';
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'chatbot_name' => 'required|string|max:255',
            'is_active'    => 'required|boolean',
            'category'     => 'required|string|max:255',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i|after:start_time',
            'last_update'  => 'nullable|date',
            'country'      => 'nullable|string|max:255',
        ]);
        $imagePath = null;
        if ($validator->fails()) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => implode(', ', $validator->errors()->all()),
                'errors'  => $validator->errors()
            ], 422);
        }
        $validated = $validator->validated();
        $country = $validated['country'] ?? $client->business_location;
        $currency = match (strtolower($country)) {
            'ghana' => 'GHS',
            'nigeria' => 'NGN',
            'United States', 'usa', 'us' => 'USD',
            'united kingdom', 'uk', 'britain', 'england' => 'GBP',
            'india' => 'INR',
            'european union', 'germany', 'france', 'italy', 'spain', 'portugal', 'netherlands', 'greece', 'austria', 'ireland' => 'EUR',
            'canada' => 'CAD',
            'australia' => 'AUD',
            'new zealand' => 'NZD',
            'south africa' => 'ZAR',
            'japan' => 'JPY',
            'china' => 'CNY',
            'russia' => 'RUB',
            'brazil' => 'BRL',
            'mexico' => 'MXN',
            'indonesia' => 'IDR',
            'philippines' => 'PHP',
            'bangladesh' => 'BDT',
            'pakistan' => 'PKR',
            'sri lanka' => 'LKR',
            'nepal' => 'NPR',
            'kenya' => 'KES',
            'uganda' => 'UGX',
            'tanzania' => 'TZS',
            'zambia' => 'ZMW',
            'malaysia' => 'MYR',
            'singapore' => 'SGD',
            'thailand' => 'THB',
            'vietnam' => 'VND',
            'south korea', 'republic of korea', 'korea' => 'KRW',
            'switzerland' => 'CHF',
            'sweden' => 'SEK',
            'denmark' => 'DKK',
            'norway' => 'NOK',
            'czech republic', 'czechia' => 'CZK',
            'poland' => 'PLN',
            'turkey' => 'TRY',
            'saudi arabia' => 'SAR',
            'uae', 'united arab emirates' => 'AED',
            'qatar' => 'QAR',
            'kuwait' => 'KWD',
            'oman' => 'OMR',
            'bahrain' => 'BHD',
            'israel' => 'ILS',
            'argentina' => 'ARS',
            'chile' => 'CLP',
            'colombia' => 'COP',
            default => 'USD',
        };

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            if ($image->isValid()) {
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('spaces', $imageName, 'public');
            }
        }
        $space = Space::create([
            'client_id'    => $client_id,
            'name'         => $validated['name'],
            'chatbot_name' => $validated['chatbot_name'],
            'is_active'    => $validated['is_active'],
            'category'     => $validated['category'],
            'currency'     => $currency,
            'country'      => $country,
            'image'        => $imagePath,
            'start_time'   => $validated['start_time'],
            'end_time'     => $validated['end_time'],
            'last_update'  => $validated['last_update'] ?? null,
        ]);
        $businessCategory = BusinessCategory::where('name', $validated['category'])->first();
        $business_description = $businessCategory?->description;
        $main_product_services = $businessCategory?->main_product_services;
        $target_customers = $businessCategory?->target_customers;
        $webhookPayload = [
            'business_name'        => $validated['name'],
            'chatbot_name'         => $validated['chatbot_name'],
            'business_type'        => $validated['category'],
            'business_description' => $business_description,
            'main_products_services' => $main_product_services,
            'target_customers'     => $target_customers,
            'communication_style'  => 'Friendly & Enthusiastic',
            'operating_hours'      => $validated['start_time'] . ' - ' . $validated['end_time'],
            'what_makes_special'   => 'A seamless, personalized experience powered by a warm, human-like assistant who remembers returning customers, simplifies every step, and offers clear, friendly support — whether it’s booking, buying, asking questions, or solving issues. Every interaction feels effortless, efficient, and genuinely helpful.',
            'image_url'            => $imagePath ? asset('storage/' . $imagePath) : null,
        ];
        $webhookResponse = Http::post($url, $webhookPayload);
        if (!$webhookResponse->successful()) {
            throw new \Exception("Webhook call failed: " . $webhookResponse->body());
        }
        $response = $webhookResponse->json();
        $output_text = trim($response['output'] ?? '');
        $output_text = preg_replace('/^```markdown\s*/', '', $output_text);
        $output_text = preg_replace('/\s*```$/', '', $output_text);
        $space_iq = space_iq::create([
            'client_id'       => $client_id,
            'space_id'        => $space->id,
            'prompt_content'  => $output_text,
        ]);
        DB::commit();
        return response()->json([
            'success'           => true,
            'message'           => 'Space created and webhook sent successfully!',
            'space'             => $space,
            'space_iq'          => $space_iq,
            'image_url'         => $imagePath ? asset('storage/' . $imagePath) : null,
            'webhook_response'  => $output_text
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack();
        if ($imagePath && Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong during creation!',
            'error'   => $e->getMessage(),
        ], 500);
    }
}


    
public function update_space(Request $request)
{
    $validated = $request->validate([
        'id'          => 'required|integer|exists:spaces,id',
        'name'         => 'required|string|max:255',
        'chatbot_name' => 'sometimes|string|max:255',
        'is_active'    => 'sometimes|boolean',
        'category'     => 'sometimes|string|max:255',
        'image'        => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg',
        'start_time'   => 'sometimes|date_format:H:i',
        'end_time'     => 'sometimes|date_format:H:i|after:start_time',
        'last_update'  => 'nullable|date',
    ]);
    $space = Space::findOrFail($validated['id']);
    if ($request->hasFile('image')) {
        if ($space->image) {
            Storage::disk('public')->delete($space->image);
        }
        $validated['image'] = $request->file('image')->store('spaces', 'public');
    }
    $space->update($validated);
    return response()->json(['success' => true, 'space' => $space, 'message'=>'space updated successfully']);
}
public function destroy($id)
{
    $space = Space::findOrFail($id);
    $space->delete();
    return response()->json(['success' => true, 'message' => 'Space deleted (soft)']);
}
public function get_spaces_list(Request $request)
{
    $client = $request->user();
    if (!$client) {
        return response()->json([
            'status'  => false,
            'message' => 'Unauthorized',
        ], 401);
    }
    $spaces = Space::where('client_id', $client->id)
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($space) {
            $imagePath='https://api.joincroose.com/croose/storage/app/public/' . $space->image;
            return [
                'id'           => $space->id,
                'uuid'         => $space->uuid,
                'client_id'    => $space->client_id,
                'space_phone'  => $space->space_phone,
                'name'         => $space->name,
                'chatbot_name' => $space->chatbot_name,
                'is_active'    => $space->is_active,
                'category'     => $space->category,
                'image'        => $imagePath,
                'start_time'   => $space->start_time,
                'end_time'     => $space->end_time,
                'last_update'  => $space->last_update,
                'deleted_at'   => $space->deleted_at,
                'created_at'   => Carbon::parse($space->created_at)->format('H:i d-M-Y'),
                'updated_at'   => Carbon::parse($space->updated_at)->format('H:i d-M-Y'),
            ];
        });
    return response()->json([
        'status'  => true,
        'count'   => $spaces->count(),
        'data'    => $spaces,
        'message' => 'Spaces User List',
    ]);
}

public function checkUserHasSpace(Request $request)
{
    $client=Auth::user();
    $client_id=$client->id;
    $hasSpace = Space::where('client_id', $client_id)->exists();
    if($hasSpace){
        $status=200;
        $message='User has created the space';
        $space_status=1;
    }
    else{
        $status=200;
        $message='Space not found';
        $space_status=0;
    }
    return response()->json([
        'status' => $status,
        'space_status'=>$space_status,
        'message' =>$message,        
    ], 201);
}

public function checkspaceIQincresed(Request $response)
{
    $client_id=Auth::user()->id;
    $space=Space::where('client_id',$client_id)->orderBy('id','asc')->first()->id;
    $space_iq_confirmation=Space_iq::where('space_id',$space)->exists();
    $iq_increased=0;
    if($space_iq_confirmation){$iq_increased=1;}
    return response()->json(['status'=>true,'iq_increased'=>$iq_increased,'message'=>'Space IQ data']);
}
public function space_iq(Request $request)
    {
        $user = Auth::user();
        DB::beginTransaction();
        try {
            $client_id = $user->id;
            $validator = Validator::make($request->all(), [
                'space_id' => 'required|integer|exists:spaces,id',
                'Prompt_content'  => 'required|string',
                'file'     => 'nullable|array|max:5',
                'file.*'   => 'file|mimes:pdf,txt', 
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors(),
                ], 422);
            }
            $validated = $validator->validated();
            $attachments = [];
            if ($request->hasFile('file')) {
                foreach ($request->file('file') as $file) {
                    if ($file && $file->isValid()) {
                        $filePath = $file->store('SpaceDocuments', 'public');
                        $fileName = $file->getClientOriginalName();
                        $mimeType = $file->getClientMimeType();
                        $attachments[] = [
                            'file_path' => $filePath,
                            'file_name' => $fileName,
                            'mime_type' => $mimeType,
                            'url'       => asset('storage/' . $filePath),
                        ];
                    }
                }
            }
            $attachmentsJson = !empty($attachments) ? json_encode($attachments) : null;
            $space_iq = space_iq::create([
                'client_id'   => $client_id,
                'space_id'    => $validated['space_id'],
                'prompt_content'     => $validated['Prompt_content'],
                'attachments' => $attachmentsJson,
            ]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'IQ submitted successfully!',
                'data'    => $space_iq,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

public function get_space_prompt(Request $request){
try{
    $validated = $request->validate([
        'space_id'=> 'required|integer|exists:spaces,id',
    ]);
    $space_id=$validated['space_id'];
    $space=space_iq::where('space_id',$space_id)->first();
    $space_prompt=$space->prompt_content;
    return response()->json([
        'status'=>true,
        'prompt_content'=>$space_prompt,
        'message'=>'Space Prompt Content',
    ]);
}
catch(\Exception $e){
    return response()->json([
        'success' => false,
        'message' => 'Something went wrong!',
        'error'   => $e->getMessage(),
    ], 500);
}
}

public function update_space_prompt(Request $request)
    {
    try {
        $validated = $request->validate([
            'space_id'       => 'required|integer|exists:spaces,id',
            'prompt_content' => 'required|string',
        ]);
        $spacePrompt = space_iq::where('space_id', $validated['space_id'])->first();
        if (!$spacePrompt) {
            return response()->json([
                'success' => false,
                'message' => 'Prompt not found for the given space_id.',
            ], 404);
        }
        $spacePrompt->prompt_content = $validated['prompt_content'];
        $spacePrompt->save();
        return response()->json([
            'status'  => true,
            'message' => 'Prompt content updated successfully.',
            'space_prompt' => $spacePrompt->prompt_content,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong!',
            'error'   => $e->getMessage(),
        ], 500);
    }
    }

public function space_chat_stats(Request $request)
{
    $client_id = Auth::user()->id;
    $validated = $request->validate([
        'space_id' => 'required|exists:spaces,id'
    ]);
    $space_id = $validated['space_id'];
    $space_uuid=Space::where('id',$space_id)->value('uuid');
    $spaceBelongsToClient = Space::where('id', $space_id)
        ->where('client_id', $client_id)
        ->exists();
    if (!$spaceBelongsToClient) {
        return response()->json(['message' => 'Space does not belong to the logged-in user'], 403);
    }
    $total_chats=Conversation::where('client_id',$client_id)
        ->where('space_id',$space_id)
        ->distinct('whatsapp_number')
        ->count();
    $whatsapp_numbers=Conversation::where('client_id',$client_id)
        ->where('space_id',$space_id)
        ->distinct('whatsapp_number')
        ->pluck('whatsapp_number');
    $total_live_chats=Customer::whereIn('whatsapp_number',$whatsapp_numbers)
        ->where('onboarding_status','1')
        ->count();
    $on_orderSales=Order::where('client_id',$client_id)
        ->where('space_id',$space_id)
        ->sum('order_amount');
    $on_appointmentSales=Appointment::where('client_id',$client_id)
        ->where('space_id',$space_id)
        ->sum('amount');
    $total_sales=$on_orderSales+$on_appointmentSales;
    return response()->json([
        'status'=>true,
        'total_chats'=>$total_chats,
        'total_live_chats'=>$total_live_chats,
        'total_sales'=>$total_sales,
        'space_uuid'=>$space_uuid,
    ]);
}
public function space_chat_list(Request $request)
{
    $client_id = Auth::user()->id;
    $validated = $request->validate([
        'space_id' => 'required|exists:spaces,id'
    ]);
    $space_id = $validated['space_id'];
    $latestConversations = DB::table('conversations as c')
        ->join(DB::raw('(
            SELECT whatsapp_number, MAX(created_at) as latest_created_at
            FROM conversations
            WHERE client_id = '.$client_id.' AND space_id = '.$space_id.'
            GROUP BY whatsapp_number
        ) as latest'), function ($join) {
            $join->on('c.whatsapp_number', '=', 'latest.whatsapp_number')
                 ->on('c.created_at', '=', 'latest.latest_created_at');
        })
        ->leftJoin('customers as cu', function ($join) {
            $join->on(DB::raw('CONVERT(c.whatsapp_number USING utf8mb4) COLLATE utf8mb4_unicode_ci'),
                      '=',
                      DB::raw('CONVERT(cu.whatsapp_number USING utf8mb4) COLLATE utf8mb4_unicode_ci'));
        })
        ->where('c.client_id', $client_id)
        ->where('c.space_id', $space_id)
        ->orderByDesc('c.created_at')
        ->get([
            'c.whatsapp_number',
            'cu.name as customer_name',
            'c.user_message',
            'c.created_at',
            'c.current_step',
        ]);
    return response()->json([
        'status' => true,
        'data' => $latestConversations,
        'message' => "Latest Conversations"
    ]);
}

}
