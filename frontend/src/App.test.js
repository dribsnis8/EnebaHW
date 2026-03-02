import { render, screen } from '@testing-library/react';
import App from './App';

test('renders the Game Store heading', () => {
  render(<App />);
  const heading = screen.getByText(/game store/i);
  expect(heading).toBeInTheDocument();
});

test('renders the search bar placeholder', () => {
  render(<App />);
  const input = screen.getByPlaceholderText(/search for a game/i);
  expect(input).toBeInTheDocument();
});
