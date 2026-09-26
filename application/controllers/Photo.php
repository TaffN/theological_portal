<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Profile photos.
 *   GET  photo/view/{userId}    - the image, if you're allowed to see it
 *   POST photo/upload           - replace your own photo
 *   POST photo/remove           - remove your own photo
 *   POST photo/upload_for/{id}  - admin replaces someone else's photo
 *
 * Who can see whose photo: yourself; admins see everyone; lecturers see
 * students and colleagues; students see lecturers (not other students).
 */
class Photo extends Auth_Controller
{
    const MAX_BYTES = 5242880;   // 5 MB before our own resizing
    const SIZE      = 512;       // stored as 512 x 512 JPEG

    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
    }

    public function view($userId)
    {
        $user = $this->User_model->find((int) $userId);
        if (! $user || ! $user['photo_path'] || ! $this->_can_see($user)) {
            show_404();
        }

        $path = FCPATH . $user['photo_path'];
        if (! is_file($path)) {
            show_404();
        }

        // Browser caching: the URL carries ?v=<timestamp>, so it can be cached
        // for a long time and still refresh the moment the photo changes.
        $etag = '"' . md5($user['photo_path'] . $user['photo_updated_at']) . '"';
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
            $this->output->set_status_header(304);
            return;
        }

        $this->output
            ->set_content_type(function_exists('mime_content_type') ? mime_content_type($path) : 'image/jpeg')
            ->set_header('Cache-Control: private, max-age=2592000')
            ->set_header('ETag: ' . $etag)
            ->set_output(file_get_contents($path));
    }

    public function upload()
    {
        $this->_store($this->current_user_id, 'profile');
    }

    public function upload_for($userId)
    {
        if ($this->current_role !== 'admin') {
            show_error('You do not have access to that.', 403);
        }
        $user = $this->User_model->find((int) $userId);
        if (! $user) {
            show_404();
        }
        $this->_store((int) $userId, 'admin_users/card/' . (int) $userId);
    }

    public function remove()
    {
        $user = $this->User_model->find($this->current_user_id);
        if ($user['photo_path'] && is_file(FCPATH . $user['photo_path'])) {
            @unlink(FCPATH . $user['photo_path']);
        }
        $this->User_model->update($this->current_user_id, ['photo_path' => null, 'photo_updated_at' => null]);
        $this->session->set_userdata('photo', null);
        $this->audit->log('profile.photo_removed', 'user', $this->current_user_id, $user['name'] . ' removed their profile photo');
        $this->session->set_flashdata('success', 'Photo removed.');
        redirect('profile');
    }

    /* ------------------------------------------------------------ */

    private function _store($userId, $back)
    {
        $file = isset($_FILES['photo']) ? $_FILES['photo'] : null;

        if (! $file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->session->set_flashdata('error', 'Please choose a photo to upload.');
            return redirect($back);
        }
        if ($file['size'] > self::MAX_BYTES) {
            $this->session->set_flashdata('error', 'That photo is too large. Please use one under 5MB.');
            return redirect($back);
        }

        // Trust the file's actual contents, not its name or what the browser claims.
        $info = @getimagesize($file['tmp_name']);
        $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
        if (! $info || ! isset($allowed[$info[2]])) {
            $this->session->set_flashdata('error', 'Please upload a JPG, PNG or WebP photo.');
            return redirect($back);
        }

        $dir = FCPATH . 'uploads/photos/';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = $userId . '_' . bin2hex(random_bytes(8));

        $saved = $this->_resize_square($file['tmp_name'], $info[2], $dir . $name . '.jpg');
        if ($saved) {
            $relPath = 'uploads/photos/' . $name . '.jpg';
        } else {
            // No GD image library on this server: keep the original (already validated above).
            $relPath = 'uploads/photos/' . $name . '.' . $allowed[$info[2]];
            if (! move_uploaded_file($file['tmp_name'], FCPATH . $relPath)) {
                $this->session->set_flashdata('error', 'Could not save the photo. Please try again.');
                return redirect($back);
            }
        }

        $user = $this->User_model->find($userId);
        if ($user['photo_path'] && is_file(FCPATH . $user['photo_path'])) {
            @unlink(FCPATH . $user['photo_path']);
        }

        $now = date('Y-m-d H:i:s');
        $this->User_model->update($userId, ['photo_path' => $relPath, 'photo_updated_at' => $now]);

        if ((int) $userId === (int) $this->current_user_id) {
            $this->session->set_userdata('photo', strtotime($now));
            $this->audit->log('profile.photo_updated', 'user', $userId, $user['name'] . ' updated their profile photo');
        } else {
            $this->audit->log('user.photo_updated', 'user', $userId, 'Updated the profile photo of ' . $user['name'] . ' (' . $user['id_number'] . ')');
        }

        $this->session->set_flashdata('success', 'Photo updated.');
        redirect($back);
    }

    /**
     * Centre-crop to a square and save as a 512px JPEG. Re-encoding also
     * strips hidden data (e.g. GPS location in phone photos) and anything
     * that isn't a real image.
     */
    private function _resize_square($src, $type, $dest)
    {
        if (! function_exists('imagecreatetruecolor')) {
            return false;
        }
        switch ($type) {
            case IMAGETYPE_JPEG: $img = @imagecreatefromjpeg($src); break;
            case IMAGETYPE_PNG:  $img = @imagecreatefrompng($src);  break;
            case IMAGETYPE_WEBP: $img = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false; break;
            default: $img = false;
        }
        if (! $img) {
            return false;
        }

        // Phone photos can be stored sideways with an "orientation" flag.
        if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($src);
            $o = isset($exif['Orientation']) ? (int) $exif['Orientation'] : 1;
            if ($o === 3) { $img = imagerotate($img, 180, 0); }
            if ($o === 6) { $img = imagerotate($img, -90, 0); }
            if ($o === 8) { $img = imagerotate($img, 90, 0); }
        }

        $w = imagesx($img);
        $h = imagesy($img);
        $side = min($w, $h);
        $out = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
        imagecopyresampled($out, $img, 0, 0, (int) (($w - $side) / 2), (int) (($h - $side) / 3), self::SIZE, self::SIZE, $side, $side);

        $ok = imagejpeg($out, $dest, 86);
        imagedestroy($img);
        imagedestroy($out);
        return $ok;
    }

    private function _can_see(array $target)
    {
        if ((int) $target['id'] === (int) $this->current_user_id) {
            return true;
        }
        switch ($this->current_role) {
            case 'admin':    return true;
            case 'lecturer': return in_array($target['role'], ['student', 'lecturer'], true);
            default:         return $target['role'] === 'lecturer';
        }
    }
}
