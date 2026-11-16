<form method="POST" action="{{ $action }}">
    @csrf
    @method($method)
    <label for="name">Name</label>
    <input id="name" name="name" type="text" value="{{ old('name', $admin?->name) }}" required>

    <label for="email">Email</label>
    <input id="email" name="email" type="email" value="{{ old('email', $admin?->email) }}" required>

    <label for="password">Password {{ $passwordRequired ? '' : '(leave blank to keep current)' }}</label>
    <input id="password" name="password" type="password" @required($passwordRequired) autocomplete="new-password">

    <label for="password_confirmation">Confirm password</label>
    <input id="password_confirmation" name="password_confirmation" type="password" @required($passwordRequired) autocomplete="new-password">

    <button type="submit">Save admin</button>
</form>
