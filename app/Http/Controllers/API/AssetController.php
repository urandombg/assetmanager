<?php

namespace App\Http\Controllers\API;

use App\Asset;
use App\Customer;
use App\Http\Resources\AssetCollection;
use App\Http\Resources\AssetResource;
use App\Media;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Support\Dataviewer;
use Illuminate\Support\Facades\App;

class AssetController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $asp = AssetResource::collection(Asset::with(['warranty',
            'customer','media',
            'maintenances'])->advancedFilter());
        return $asp;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $asset = new Asset();
        $asset->type_of_asset = $request->type_of_asset;
        $asset->model = $request->model;
        $asset->location = $request->location;
        $asset->customer_id = ($request->customer_id == null ? 2 : $request->customer_id);
        $asset->serial = $request->serial;
        $asset->purchaseDate = $request->purchaseDate;
        $asset->department = $request->department;
        $asset->title = $request->title;

        $asset->saveOrFail();

        return response()->json(['message' => 'Item has been saved!', 'asset_id' => $asset->id]);
    }

    public function addComponentToAsset(Request $request)
    {
        $asset = Asset::findOrFail($request->asset_id);
        $asset->components()->create([
            'title' => $request->title,
            'model' => $request->model,
            'serial' => $request->serial
        ]);
        return response()->json('opk');
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        $asset = new AssetResource(Asset::findOrFail($id)->load(['components', 'warranty','media', 'maintenances']));

        return $asset;
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
//        dd($request->all());

        $assetToUpdate = Asset::find($id);
        $assetToUpdate->type_of_asset = $request->type_of_asset;
        $assetToUpdate->model = $request->model;
        $assetToUpdate->location = $request->location;
//        $assetToUpdate->customer_id = ($request->customer_id == null ? 1 : $request->customer_id);
        $assetToUpdate->customer_id = $request->customer_id;
        $assetToUpdate->serial = $request->serial;
        $assetToUpdate->purchaseDate = $request->purchaseDate;
        $assetToUpdate->department = $request->department;
        $assetToUpdate->title = $request->title;

        $assetToUpdate->save();
        return response()->json('ok');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $asset = Asset::destroy($id);

    }
    public function primaryAssets() {
        $asset = Asset::ofType('primary')
        ->with(['components','warranty','customer','media'])
        ->paginate();
        return response()->json($asset);
    }
    public function secondaryAssets() {
        $asset = Asset::ofType('primary')->paginate();
        return response()->json($asset);
    }

    public function saveAssetMedia(Request $request)
    {
        $assetMediaStore = new Media();
        $assetMediaStore->media_type = $request->file('file')->getMimeType();
        $assetMediaStore->size = $request->file('file')->getSize();
        $assetMediaStore->pathToMedia = '/storage/imgs/assets/' .$request->asset_id . '/' . $request->file('file')->getClientOriginalName();
        $assetMediaStore->url = '/storage/imgs/assets/';
        $assetMediaStore->saveOrFail();

        $assetMediaStore->assets()->newPivot([
            'asset_id' => $request->asset_id,
            'media_id' => $assetMediaStore->id
        ])->save();

        $request->file('file')
            ->storePubliclyAs('/public/imgs/assets/' . $request->asset_id, $request->file('file')
            ->getClientOriginalName() );

        return response()->json('ok');
    }

    public function search(Request $request)
    {
        $keyword = $request->search;
        $requestSQ = Asset::where('serial','like', '%'. $request->search .'%');
        $results = AssetResource::collection($requestSQ->paginate());

        return $results;
    }

    public function addMaintenanceToExistingProtocol(Request $request)
    {
        $protocol = Asset::findOrFail($request->asset_id)->load(['warranty', 'maintenances']);
    }
    public function createMaintenanceProtocol(Request $request)
    {

        $asset = Asset::findOrFail($request->asset_id)->load(['warranty', 'maintenances']);

//        $asset = \App\Asset::findOrFail(1)->load(['components', 'warranty', 'maintenances']);
//

        $asset->maintenances()->create([
            'perform_on' => Carbon::parse($request->perform_on),
            'protocolUUID' => \Ramsey\Uuid\Uuid::uuid4(),
            'isWarrantyEvent' => $request->isWarrantyEvent,
            'explanation' => $request->explanation,
        ]);
        return response()->json('ok');
    }

    /**
     * @params:
     *      id
     *
     * */
    public function changeOwner(Request $request)
    {
        $asset = Asset::findOrFail($request->asset_id);
        $asset->customer_id = $request->newId;
        $asset->save();

        return response()->json(['message' => 'ok']);

    }

    public function createWarranty(Request $request)
    {
        $asset = Asset::findOrFail($request->id)->load('customer');
//        dd($asset);

        $war = new \App\Warranty();
        $war->card_id = \Ramsey\Uuid\Uuid::uuid4();
        $war->start = \Carbon\Carbon::today();
        $war->end = \Carbon\Carbon::today()->addYears(2);
        $war->periodInDays = 1534;
        $war->asset_id = $asset->id;

        $war->generateAssetWarrantyCardFromToday($asset);

        $war->save();

        return response()->json(['message' => 'Warranty has been saved!']);
    }
}
