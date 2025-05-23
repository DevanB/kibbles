import { useForm } from '@inertiajs/react'
import { FormEvent } from 'react'
import { GameFormType, GameStatus, GameType } from './types'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'

// Temporary fix for InertiaFormProps not being exported from @inertiajs/react
type InertiaFormProps<TForm extends Record<string, any>> = ReturnType<typeof useForm<TForm>>

interface FormProps {
  game: GameType
  onSubmit: (form: InertiaFormProps<GameFormType>) => void
  submitText: string
}

export default function Form({ game, onSubmit, submitText }: FormProps) {
  const form = useForm<GameFormType>({
    name: game.name || "",
    status: game.status
  })
  const { data, setData, errors, processing } = form

  const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    onSubmit(form)
  }

  return (
    <form onSubmit={handleSubmit} className="contents">
      <div className="my-5">
        <Label htmlFor="name">Name</Label>
        <Input
          type="text"
          name="name"
          id="name"
          value={data.name}
          placeholder="Ace Attorney: Phoenix Wright"
          className="block shadow rounded-md border border-gray-400 outline-none px-3 py-2 mt-2 w-full"
          onChange={(e) => setData('name', e.target.value)}
        />
        {errors.name && (
          <div className="text-red-500 px-3 py-2 font-medium">
            {errors.name}
          </div>
        )}
      </div>

      <div className="my-5 space-y-2">
        <Label>Status</Label>
        <RadioGroup id="status" value={data.status} onValueChange={(value) => setData('status', value as GameStatus)}>
          <div className="flex items-center space-x-2">
            <RadioGroupItem value="to_play" id="to_play" />
            <Label htmlFor="to_play">To Play</Label>
          </div>
          <div className="flex items-center space-x-2">
            <RadioGroupItem value="in_progress" id="in_progress" />
            <Label htmlFor="in_progress">In Progress</Label>
          </div>
          <div className="flex items-center space-x-2">
            <RadioGroupItem value="completed" id="completed" />
            <Label htmlFor="completed">Completed</Label>
          </div>
          <div className="flex items-center space-x-2">
            <RadioGroupItem value="abandoned" id="abandoned" />
            <Label htmlFor="abandoned">Abandoned</Label>
          </div>
        </RadioGroup>
        {errors.status && (
          <div className="text-red-500 px-3 py-2 font-medium">
            {errors.status}
          </div>
        )}
      </div>

      <div className="inline">
        <Button
          type="submit"
          disabled={processing}
          variant="default"
          className="cursor-pointer"
        >
          {submitText}
        </Button>
      </div>
    </form>
  )
}
