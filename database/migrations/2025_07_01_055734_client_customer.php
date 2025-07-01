<!-- 
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;

class ClientCustomerController extends Controller
{
    public function attachCustomer(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
        ]);

        $client = auth()->user(); 

        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }

        $client->customers()->syncWithoutDetaching([$validated['customer_id']]);

        return response()->json([
            'success' => true,
            'message' => 'Customer attached to client successfully.'
        ]);
    }
} -->
