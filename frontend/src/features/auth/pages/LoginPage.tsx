import { useAuthStore } from '@/shared/stores/authStore';
import { zodResolver } from '@hookform/resolvers/zod';
import { AlertCircle, Eye, EyeOff, Loader2, Truck } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { Link, useNavigate } from 'react-router-dom';
import { useAuthContext } from '../context/AuthContext';
import { loginSchema, type LoginFormData } from '../schemas/authSchema';

export function LoginPage() {
  const navigate = useNavigate();
  const { login, loginError, isLoginPending } = useAuthContext();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const [showPassword, setShowPassword] = useState(false);
  const [serverFieldErrors, setServerFieldErrors] = useState<Record<string, string> | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LoginFormData>({
    resolver: zodResolver(loginSchema),
  });

  useEffect(() => {
  if (isAuthenticated) {
    navigate('/app/dashboard', { replace: true });
  }
}, [isAuthenticated, navigate]);

  async function onSubmit(data: LoginFormData) {
    setServerFieldErrors(null);
    try {
      await login(data);
      navigate('/app/dashboard', { replace: true });
    } catch (err: unknown) {
      const serverErrors =
        err && typeof err === 'object' && 'response' in err
          ? (err as { response: { data: { errors?: Record<string, string[]> } } }).response.data
              ?.errors
          : undefined;
      if (serverErrors) {
        const flat: Record<string, string> = {};
        for (const [key, msgs] of Object.entries(serverErrors)) {
          flat[key] = msgs[0];
        }
        setServerFieldErrors(flat);
      }
    }
  }

  function fieldError(name: keyof LoginFormData): string | undefined {
    return errors[name]?.message ?? serverFieldErrors?.[name];
  }

  const inputStyle = (hasError: boolean): React.CSSProperties => ({
    width: '100%',
    padding: 'var(--space-3) var(--space-3)',
    fontSize: 'var(--text-sm)',
    color: 'var(--color-text)',
    background: 'var(--color-surface)',
    border: `1px solid ${hasError ? 'var(--color-danger)' : 'var(--color-border)'}`,
    borderRadius: 'var(--radius-md)',
    outline: 'none',
    transition: 'border-color var(--transition-fast)',
  });

  return (
    <div
      style={{
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background: 'var(--color-bg)',
        padding: 'var(--space-4)',
      }}
    >
      <div
        className="card"
        style={{
          width: '100%',
          maxWidth: 400,
          padding: 'var(--space-8)',
        }}
      >
        <div style={{ textAlign: 'center', marginBottom: 'var(--space-6)' }}>
          <div
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              justifyContent: 'center',
              width: 48,
              height: 48,
              borderRadius: 'var(--radius-xl)',
              background: 'var(--color-primary-light)',
              color: 'var(--color-primary)',
              marginBottom: 'var(--space-3)',
            }}
          >
            <Truck size={24} />
          </div>
          <h1 style={{ fontSize: 'var(--text-2xl)', marginBottom: 'var(--space-1)' }}>
            Transport Manager
          </h1>
          <p style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)' }}>
            Sign in to your account
          </p>
        </div>

        <form onSubmit={handleSubmit(onSubmit)} noValidate>
          {loginError && !serverFieldErrors && (
            <div
              role="alert"
              style={{
                display: 'flex',
                alignItems: 'center',
                gap: 'var(--space-2)',
                padding: 'var(--space-3)',
                marginBottom: 'var(--space-4)',
                background: 'var(--color-danger-light)',
                border: '1px solid var(--red-200)',
                borderRadius: 'var(--radius-md)',
                fontSize: 'var(--text-sm)',
                color: 'var(--color-danger)',
              }}
            >
              <AlertCircle size={16} aria-hidden="true" />
              <span>{loginError}</span>
            </div>
          )}

          <div style={{ marginBottom: 'var(--space-4)' }}>
            <label
              htmlFor="email"
              style={{
                display: 'block',
                fontSize: 'var(--text-sm)',
                fontWeight: 500,
                marginBottom: 'var(--space-1)',
                color: 'var(--color-text)',
              }}
            >
              Email
            </label>
            <input
              id="email"
              type="email"
              autoComplete="email"
              autoFocus
              {...register('email')}
              style={inputStyle(!!fieldError('email'))}
              aria-invalid={!!fieldError('email')}
              aria-describedby={fieldError('email') ? 'email-error' : undefined}
              placeholder="you@example.com"
            />
            {fieldError('email') && (
              <p
                id="email-error"
                role="alert"
                style={{
                  fontSize: 'var(--text-xs)',
                  color: 'var(--color-danger)',
                  marginTop: 2,
                }}
              >
                {fieldError('email')}
              </p>
            )}
          </div>

          <div style={{ marginBottom: 'var(--space-6)' }}>
            <label
              htmlFor="password"
              style={{
                display: 'block',
                fontSize: 'var(--text-sm)',
                fontWeight: 500,
                marginBottom: 'var(--space-1)',
                color: 'var(--color-text)',
              }}
            >
              Password
            </label>
            <div style={{ position: 'relative' }}>
              <input
                id="password"
                type={showPassword ? 'text' : 'password'}
                autoComplete="current-password"
                {...register('password')}
                style={{
                  ...inputStyle(!!fieldError('password')),
                  paddingRight: 40,
                }}
                aria-invalid={!!fieldError('password')}
                aria-describedby={fieldError('password') ? 'password-error' : undefined}
                placeholder="Enter your password"
              />
              <button
                type="button"
                onClick={() => setShowPassword((p) => !p)}
                style={{
                  position: 'absolute',
                  right: 8,
                  top: '50%',
                  transform: 'translateY(-50%)',
                  background: 'none',
                  border: 'none',
                  cursor: 'pointer',
                  color: 'var(--color-text-muted)',
                  padding: 4,
                }}
                aria-label={showPassword ? 'Hide password' : 'Show password'}
                tabIndex={-1}
              >
                {showPassword ? <EyeOff size={16} /> : <Eye size={16} />}
              </button>
            </div>
            {fieldError('password') && (
              <p
                id="password-error"
                role="alert"
                style={{
                  fontSize: 'var(--text-xs)',
                  color: 'var(--color-danger)',
                  marginTop: 2,
                }}
              >
                {fieldError('password')}
              </p>
            )}
          </div>

          <button
            type="submit"
            className="btn btn-primary"
            disabled={isLoginPending}
            style={{
              width: '100%',
              justifyContent: 'center',
              padding: 'var(--space-3)',
              fontSize: 'var(--text-base)',
            }}
          >
            {isLoginPending ? (
              <>
                <Loader2 size={16} className="spin" />
                Signing in...
              </>
            ) : (
              'Sign in'
            )}
          </button>
        </form>

        <div style={{ textAlign: 'center', marginTop: 'var(--space-4)' }}>
          <Link
            to="/forgot-password"
            style={{
              fontSize: 'var(--text-sm)',
              color: 'var(--color-primary)',
              textDecoration: 'none',
            }}
          >
            Forgot your password?
          </Link>
        </div>
      </div>
    </div>
  );
}
