import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2, ArrowLeft, Mail } from 'lucide-react';
import { forgotPasswordSchema, type ForgotPasswordFormData } from '../schemas/authSchema';
import axiosClient from '@/shared/api/axiosClient';
import { fetchCsrfCookie } from '@/shared/api/apiEnvelope';

export function ForgotPasswordPage() {
  const [submitted, setSubmitted] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [isPending, setIsPending] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<ForgotPasswordFormData>({
    resolver: zodResolver(forgotPasswordSchema),
  });

  async function onSubmit(data: ForgotPasswordFormData) {
    setIsPending(true);
    setError(null);
    try {
      await fetchCsrfCookie();
      await axiosClient.post('/auth/forgot-password', data);
      setSubmitted(true);
    } catch {
      setError('Failed to send reset link. Please try again.');
    } finally {
      setIsPending(false);
    }
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
            <Mail size={24} />
          </div>
          <h1 style={{ fontSize: 'var(--text-2xl)', marginBottom: 'var(--space-1)' }}>
            Reset password
          </h1>
          <p style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)' }}>
            Enter your email and we will send you a reset link.
          </p>
        </div>

        {submitted ? (
          <div style={{ textAlign: 'center' }}>
            <p
              style={{
                color: 'var(--color-success)',
                fontSize: 'var(--text-sm)',
                marginBottom: 'var(--space-4)',
              }}
            >
              If an account with that email exists, a password reset link has been sent.
            </p>
            <Link
              to="/login"
              style={{
                display: 'inline-flex',
                alignItems: 'center',
                gap: 'var(--space-2)',
                fontSize: 'var(--text-sm)',
                color: 'var(--color-primary)',
                textDecoration: 'none',
              }}
            >
              <ArrowLeft size={16} />
              Back to login
            </Link>
          </div>
        ) : (
          <>
            <form onSubmit={handleSubmit(onSubmit)} noValidate>
              {error && (
                <div
                  role="alert"
                  style={{
                    padding: 'var(--space-3)',
                    marginBottom: 'var(--space-4)',
                    background: 'var(--color-danger-light)',
                    border: '1px solid var(--red-200)',
                    borderRadius: 'var(--radius-md)',
                    fontSize: 'var(--text-sm)',
                    color: 'var(--color-danger)',
                  }}
                >
                  {error}
                </div>
              )}

              <div style={{ marginBottom: 'var(--space-6)' }}>
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
                  style={inputStyle(!!errors.email)}
                  aria-invalid={!!errors.email}
                  aria-describedby={errors.email ? 'email-error' : undefined}
                  placeholder="you@example.com"
                />
                {errors.email && (
                  <p
                    id="email-error"
                    role="alert"
                    style={{
                      fontSize: 'var(--text-xs)',
                      color: 'var(--color-danger)',
                      marginTop: 2,
                    }}
                  >
                    {errors.email.message}
                  </p>
                )}
              </div>

              <button
                type="submit"
                className="btn btn-primary"
                disabled={isPending}
                style={{
                  width: '100%',
                  justifyContent: 'center',
                  padding: 'var(--space-3)',
                  fontSize: 'var(--text-base)',
                }}
              >
                {isPending ? (
                  <>
                    <Loader2 size={16} className="spin" />
                    Sending...
                  </>
                ) : (
                  'Send reset link'
                )}
              </button>
            </form>

            <div style={{ textAlign: 'center', marginTop: 'var(--space-4)' }}>
              <Link
                to="/login"
                style={{
                  display: 'inline-flex',
                  alignItems: 'center',
                  gap: 'var(--space-2)',
                  fontSize: 'var(--text-sm)',
                  color: 'var(--color-primary)',
                  textDecoration: 'none',
                }}
              >
                <ArrowLeft size={16} />
                Back to login
              </Link>
            </div>
          </>
        )}
      </div>
    </div>
  );
}
