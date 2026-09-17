<?php

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Models\Media\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver; // Ou Imagick, consoante o configurado no PHP

class MediaController extends Controller
{
    // =====================================================================
    // 1. FLUXO DE IMAGENS (Via Servidor Laravel com conversão WebP)
    // =====================================================================

    /**
     * Faz upload de imagens normais, convertendo-as para WebP no processo.
     */
    public function uploadImage(Request $request)
    {
        $validated = $request->validate([
            'file'           => 'required|image|max:10240', // Max 10MB
            'event_id'       => 'nullable|exists:events,id',
            'privacy_status' => 'required|in:public,private',
            'year'           => 'nullable|integer'
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $disk = $validated['privacy_status'] === 'public' ? 's3_public' : 's3_private';
        $year = $validated['year'] ?? date('Y');
        $pathPrefix = "events/{$year}";

        // Inicia o motor do Intervention Image e converte
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getRealPath());
        $encodedImage = $image->toWebp(80); // 80% de qualidade
        
        $fileName = Str::uuid() . '.webp';
        $fullPath = "{$pathPrefix}/{$fileName}";
        
        // Grava no disco S3 correto
        Storage::disk($disk)->put($fullPath, (string) $encodedImage);

        // Grava os metadados na nossa Base de Dados
        $media = Media::create([
            'event_id'       => $validated['event_id'] ?? null,
            'year'           => $year,
            'original_name'  => $originalName,
            'file_name'      => $fileName,
            'path'           => $fullPath,
            'mime_type'      => 'image/webp',
            'size'           => strlen((string) $encodedImage),
            'disk'           => $disk,
            'privacy_status' => $validated['privacy_status'],
            'glacier_status' => 'standard'
        ]);

        return response()->json([
            'message' => 'Imagem processada e enviada com sucesso.', 
            'data'    => $media
        ], 201);
    }

    // =====================================================================
    // 2. FLUXO MULTIPART PARA VÍDEOS DE 10GB (Direct-to-S3 do Frontend)
    // =====================================================================

    /**
     * Passo 1: O Frontend diz que vai enviar um vídeo grande. Abrimos a sessão.
     */
    public function initiateMultipart(Request $request)
    {
        $validated = $request->validate([
            'file_name'      => 'required|string',
            'mime_type'      => 'required|string',
            'privacy_status' => 'required|in:public,private',
            'year'           => 'nullable|integer'
        ]);

        $disk = $validated['privacy_status'] === 'public' ? 's3_public' : 's3_private';
        $year = $validated['year'] ?? date('Y');
        
        $extension = pathinfo($validated['file_name'], PATHINFO_EXTENSION);
        $uniqueFileName = Str::uuid() . '.' . $extension;
        $path = "events/{$year}/{$uniqueFileName}";

        $client = Storage::disk($disk)->getClient();

        // Pede à AWS um Upload ID
        $result = $client->createMultipartUpload([
            'Bucket'      => config("filesystems.disks.{$disk}.bucket"),
            'Key'         => $path,
            'ContentType' => $validated['mime_type'],
        ]);

        return response()->json([
            'upload_id'     => $result['UploadId'],
            'key'           => $path,
            'file_name'     => $uniqueFileName,
            'original_name' => $validated['file_name'],
            'disk'          => $disk,
        ]);
    }

    /**
     * Passo 2: O Frontend pede a URL para enviar um pedaço (ex: Parte 1 de 50MB).
     */
    public function signPart(Request $request)
    {
        $request->validate([
            'key'         => 'required|string',
            'upload_id'   => 'required|string',
            'part_number' => 'required|integer',
            'disk'        => 'required|in:s3_public,s3_private'
        ]);

        $client = Storage::disk($request->disk)->getClient();
        
        $command = $client->getCommand('UploadPart', [
            'Bucket'     => config("filesystems.disks.{$request->disk}.bucket"),
            'Key'        => $request->key,
            'UploadId'   => $request->upload_id,
            'PartNumber' => $request->part_number,
        ]);

        // Gera uma URL temporária válida apenas por 15 minutos para esta parte
        $url = (string) $client->createPresignedRequest($command, '+15 minutes')->getUri();

        return response()->json(['url' => $url]);
    }

    /**
     * Passo 3: O Frontend enviou tudo. Fechamos a sessão S3 e gravamos na BD.
     */
    public function completeMultipart(Request $request)
    {
        $validated = $request->validate([
            'event_id'           => 'nullable|exists:events,id',
            'year'               => 'nullable|integer',
            'original_name'      => 'required|string',
            'file_name'          => 'required|string',
            'path'               => 'required|string',
            'mime_type'          => 'required|string',
            'size'               => 'required|integer',
            'privacy_status'     => 'required|in:public,private',
            'disk'               => 'required|in:s3_public,s3_private',
            'upload_id'          => 'required|string',
            
            // AWS exige que se devolva um array com os eTags de todas as partes
            'parts'              => 'required|array',
            'parts.*.PartNumber' => 'required|integer',
            'parts.*.ETag'       => 'required|string',
        ]);

        $client = Storage::disk($validated['disk'])->getClient();

        // Avisa a Amazon para colar os pedaços todos num ficheiro final
        $client->completeMultipartUpload([
            'Bucket'   => config("filesystems.disks.{$validated['disk']}.bucket"),
            'Key'      => $validated['path'],
            'UploadId' => $validated['upload_id'],
            'MultipartUpload' => [
                'Parts' => $validated['parts']
            ],
        ]);

        // Grava a meta-informação final na BD
        $media = Media::create([
            'event_id'       => $validated['event_id'] ?? null,
            'year'           => $validated['year'] ?? date('Y'),
            'original_name'  => $validated['original_name'],
            'file_name'      => $validated['file_name'],
            'path'           => $validated['path'],
            'mime_type'      => $validated['mime_type'],
            'size'           => $validated['size'],
            'disk'           => $validated['disk'],
            'privacy_status' => $validated['privacy_status'],
            'glacier_status' => 'standard'
        ]);

        return response()->json([
            'message' => 'Upload de ficheiro gigante concluído e metadados guardados.',
            'data'    => $media
        ], 201);
    }

    // =====================================================================
    // 3. GESTÃO, VISUALIZAÇÃO E CICLO DE VIDA (GLACIER)
    // =====================================================================

    /**
     * Recupera a URL de um ficheiro (gerindo permissões e o estado Glacier).
     */
    public function show(Media $media)
    {
        // 1. Atualiza a coluna last_accessed_at (usado pelo Job do Glacier)
        $media->recordAccess();

        // 2. Se o ficheiro estiver no arquivo profundo (Glacier)
        if ($media->isArchived()) {
            return response()->json([
                'message' => 'O ficheiro está arquivado no Glacier. O restauro será iniciado automaticamente. Por favor volte a tentar em 3 a 5 horas.',
                'status'  => 'restoring'
            ], 202); // Código 202: Accepted (Pedido aceite mas não pronto)
        }

        // 3. Se estiver acessível, gera a URL correta baseada na privacidade
        if ($media->privacy_status === 'private') {
            // Ficheiros privados precisam de URL assinada que expira
            $url = Storage::disk($media->disk)->temporaryUrl(
                $media->path, now()->addMinutes(60) // Expira em 1 hora
            );
        } else {
            // Ficheiros públicos (ex: imagens) têm link direto standard
            $url = Storage::disk($media->disk)->url($media->path);
        }

        return response()->json([
            'media' => $media,
            'url'   => $url
        ]);
    }

    /**
     * Elimina um ficheiro da S3 e da Base de Dados.
     */
    public function destroy(Media $media)
    {
        if (Storage::disk($media->disk)->exists($media->path)) {
            Storage::disk($media->disk)->delete($media->path);
        }
        
        $media->delete();

        return response()->json(['message' => 'Ficheiro eliminado fisicamente com sucesso.']);
    }
}