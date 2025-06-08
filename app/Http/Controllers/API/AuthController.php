<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;


class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/user/register",
     *     summary="Register a new user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *             @OA\Property(property="password", type="string", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="token123"),
     *             @OA\Property(property="token_type", type="string", example="Bearer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['access_token' => $token, 'token_type' => 'Bearer']);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/user/login",
     *     summary="Authenticate a user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User authenticated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="token123"),
     *             @OA\Property(property="token_type", type="string", example="Bearer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials"
     *     )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['access_token' => $token, 'token_type' => 'Bearer']);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/user/forgot-password",
     *     summary="Send password reset token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *             @OA\Property(property="reset_url", type="string", example="https://yourfrontend.com/reset-password?token={token}&email={email}")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset token sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password reset token generated successfully"),
     *             @OA\Property(property="token", type="string", example="abc123def456"),
     *             @OA\Property(property="reset_link", type="string", example="https://yourfrontend.com/reset-password?token=xxx&email=user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'reset_url' => 'nullable|url'
        ]);
        
        // Find the user by email
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json([
                'message' => 'We have sent a password reset token if the email exists in our system.'
            ], 200); // Don't reveal if email exists for security
        }
        
        // Generate a new reset token
        $token = Str::random(64);
        
        // Delete any existing tokens for this email
        \DB::table('password_resets')
            ->where('email', $request->email)
            ->delete();
            
        // Store the new token in the password_resets table
        \DB::table('password_resets')->insert([
            'email' => $request->email,
            'token' => Hash::make($token),
            'created_at' => now()
        ]);
        
        $response = [
            'message' => 'Password reset token generated successfully',
            'token' => $token
        ];
        
        // If reset_url is provided, also generate a clickable link
        if ($request->has('reset_url') && $request->reset_url) {
            $resetLink = str_replace(
                ['{token}', '{email}'], 
                [$token, $request->email], 
                $request->reset_url
            );
            $response['reset_link'] = $resetLink;
        }
        
        return response()->json($response);
    }
    
    /**
     * @OA\Post(
     *     path="/api/v1/user/reset-password",
     *     summary="Reset user password",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token", "email", "password", "password_confirmation"},
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *             @OA\Property(property="password", type="string", example="newpassword123"),
     *             @OA\Property(property="password_confirmation", type="string", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password reset successfully"),
     *             @OA\Property(property="login_url", type="string", example="/login")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid token or expired token"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        // Find the user by email
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json([
                'message' => 'Invalid or expired password reset link'
            ], 400);
        }
        
        // Find the token in the password_resets table
        $passwordReset = \DB::table('password_resets')
            ->where('email', $request->email)
            ->first();
        
        if (!$passwordReset) {
            return response()->json([
                'message' => 'Invalid or expired password reset link'
            ], 400);
        }
        
        // Verify the token
        if (!Hash::check($request->token, $passwordReset->token)) {
            return response()->json([
                'message' => 'Invalid or expired password reset link'
            ], 400);
        }
        
        // Check if token is expired (tokens last 60 minutes)
        $tokenCreatedAt = new \Carbon\Carbon($passwordReset->created_at);
        if (now()->diffInMinutes($tokenCreatedAt) > 60) {
            return response()->json([
                'message' => 'Password reset link has expired'
            ], 400);
        }
        
        // Update the user's password
        $user->password = Hash::make($request->password);
        $user->setRememberToken(Str::random(60));
        $user->save();
        
        // Delete the used token
        \DB::table('password_resets')
            ->where('email', $request->email)
            ->delete();
        
        // Revoke all tokens so user has to login again
        $user->tokens()->delete();
        
        return response()->json([
            'message' => 'Your password has been reset successfully',
            'login_url' => '/login' // Frontend can redirect to this URL
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/logout",
     *     summary="Logout a user",
     *     tags={"Authentication"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Response(
     *         response=200,
     *         description="User logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User logged out successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function logout(Request $request)
    {
        // Revoke all tokens...
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'User logged out successfully'
        ]);
    }
}